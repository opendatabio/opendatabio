# Programing directives and naming conventions

1. This software should adhere to Semantic Versioning, starting from version 0.1.0-alpha1. The companion R package should follow a similar versioning scheme.
1. All variables and functions should be named in English, with entities and fields related to the database being named in the singular form. All tables (where apropriate) should have an "id" column, and foreign keys should reference the base table with "_id" suffix, except in cases of self-joins (such as "taxon.parent_id") or [polymorphic foreign keys](core_objects#polymorphicrelationships). The id of each table has type INT and should be autoincrementing.
1. There should be a structure to store which **Plugins** are installed on a given database and which are the compatible system versions.

## Locale - Translating the interface
