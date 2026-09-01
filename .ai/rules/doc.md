---
paths:
  - 'doc/**'
---

# Doc

## Conventions de documentation
Ne créer un fichier de documentation que si l'utilisateur le demande explicitement. Rédiger en français.
- `prds/` : exigences fonctionnelles et cas d'usage uniquement, aucun élément de design.
- `plans/` : étapes d'implémentation pas à pas, organisées en lots de livraison avec cases à cocher ; mettre à jour le plan lui-même, pas de document de synthèse ou de todo à côté.
- `design/` : architecture, minimaliste ; lister les alternatives écartées et pourquoi.
- `users/` : orienté how-to.
- Diagrammes PlantUML : sources `.puml` dans un sous-dossier `diagrams/` voisin du document ; régénérer les images avec `bin/puml.sh` et committer `.puml` + `.png` ensemble (la CI vérifie qu'ils changent dans le même diff).
- Quand un PRD, un design ou un plan change, mettre à jour les documents liés.
