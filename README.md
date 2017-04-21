# opendatabio
Um sistema moderno de informação sobre plantas - florística, ecologia e monitoramento 

## Modelo de dados

### Entidades focais

- Object:
  - Location
  - Taxon
  - Plant
  - Voucher
  - Sample
- Identification (rel Plant x Taxon)
- Trait (+ Category, Measurement)

### Entidades secundárias
- Person
- Reference
- Herbarium
- Image
- Census
- Project

### Interface / acesso
- User (conecta com Person)
- Role (a principio, "normal" ou "admin")
- Language
- Translation
- DataTranslation (TALVEZ, para traduzir dados de variável de usuário?)
- Plugin
- Access (Relação Role x privilégio)
- DataAccess (Para guardar autorização do tipo "cadastre seu e-mail")
- Config (configurações de site - titulo, tag, proxy, etc)

### Busca e inserção
- Form
- Filter
- Report
- Job (para realizar tarefas em background)

### AUDITORIA

### TABELAS AUXILIARES (importação de dados; relatórios)

