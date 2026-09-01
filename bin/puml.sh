#!/usr/bin/env bash
# Régénère les images des diagrammes PlantUML à partir des sources .puml.
#
# Sources : doc/**/*.puml  ->  image PNG écrite à côté de chaque source.
# Moteur  : jar PlantUML épinglé (téléchargé dans bin/.cache/, ignoré par Git),
#           exécuté avec java, pour une sortie identique d'une machine à l'autre.
# Surcharge du moteur : PLANTUML="commande" — p. ex.
#   PLANTUML='docker run --rm -v "$PWD":/w -w /w plantuml/plantuml:1.2025.4'
#
# Usage :
#   bin/puml.sh            régénère toutes les images
#   bin/puml.sh --check    ne régénère rien ; sort en erreur si une image est
#                          absente ou diffère de ce que produirait le jar
#                          (à lancer en local avant de committer)
set -uo pipefail

PLANTUML_VERSION="1.2025.4"
FORMAT="png"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

CACHE_DIR="bin/.cache"
JAR="${CACHE_DIR}/plantuml-${PLANTUML_VERSION}.jar"

check_only=0
if [[ "${1:-}" == "--check" ]]; then
    check_only=1
fi

run_plantuml() {
    if [[ -n "${PLANTUML:-}" ]]; then
        eval "${PLANTUML}" "$@"
    else
        java -jar "${JAR}" "$@"
    fi
}

# Prépare le moteur par défaut (jar + java) si aucune surcharge PLANTUML.
if [[ -z "${PLANTUML:-}" ]]; then
    if ! command -v java >/dev/null 2>&1; then
        echo "java introuvable : installez un JRE, ou définissez PLANTUML=\"...\"." >&2
        exit 1
    fi

    if [[ ! -f "${JAR}" ]]; then
        echo "Téléchargement de PlantUML ${PLANTUML_VERSION}…"
        mkdir -p "${CACHE_DIR}"
        url="https://github.com/plantuml/plantuml/releases/download/v${PLANTUML_VERSION}/plantuml-${PLANTUML_VERSION}.jar"
        if ! curl -fsSL -o "${JAR}.tmp" "${url}"; then
            echo "Échec du téléchargement : ${url}" >&2
            rm -f "${JAR}.tmp"
            exit 1
        fi
        mv "${JAR}.tmp" "${JAR}"
    fi
fi

mapfile -t sources < <(find doc -name '*.puml' | sort)

if [[ ${#sources[@]} -eq 0 ]]; then
    echo "Aucun fichier .puml sous doc/."
    exit 0
fi

status=0

if [[ ${check_only} -eq 1 ]]; then
    tmp="$(mktemp -d)"
    trap 'rm -rf "${tmp}"' EXIT

    for src in "${sources[@]}"; do
        base="$(basename "${src}" .puml)"
        committed="$(dirname "${src}")/${base}.${FORMAT}"

        if ! run_plantuml -t"${FORMAT}" -o "${tmp}" "${src}" >/dev/null 2>&1; then
            echo "✗ ${src} : échec du rendu"
            status=1
            continue
        fi

        if [[ ! -f "${committed}" ]]; then
            echo "✗ ${committed} : absent"
            status=1
        elif ! cmp -s "${committed}" "${tmp}/${base}.${FORMAT}"; then
            echo "✗ ${committed} : obsolète (source modifiée sans régénération)"
            status=1
        else
            echo "✓ ${committed}"
        fi
    done

    if [[ ${status} -eq 0 ]]; then
        echo "Diagrammes à jour."
    else
        echo "Lancez bin/puml.sh puis committez les images." >&2
    fi

    exit ${status}
fi

for src in "${sources[@]}"; do
    echo "▶ ${src}"
    if ! run_plantuml -t"${FORMAT}" "${src}"; then
        status=1
    fi
done

if [[ ${status} -eq 0 ]]; then
    echo "✓ Diagrammes régénérés."
else
    echo "✗ Au moins un diagramme a échoué." >&2
fi

exit ${status}
