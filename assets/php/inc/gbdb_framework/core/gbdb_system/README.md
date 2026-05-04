# GBDB System

Der Ordner enthält nur noch zwei Hauptbereiche:

```text
gbdb_system/
├── gbdb/
│   ├── v1/      # GBDB v1 Traits
│   └── v2/      # GBDBv2 Traits
└── greenql/
    ├── v1/      # GreenQL v1 Traits
    └── v2/      # GreenQLv2 Traits
```

Damit bleibt die Trennung fachlich klar: `gbdb` enthält die Datenbank-Engine, `greenql` die Sprache/Runtime. Die Versionierung liegt jeweils darunter.
