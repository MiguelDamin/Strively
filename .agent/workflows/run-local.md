---
description: como rodar o Strively localmente via Docker
---

// turbo-all

1. Acesse o diretório do projeto:
```
cd /var/www/html/Strively
```

2. Build da imagem local (só precisa rodar quando houver mudanças no Dockerfile.local ou dependências):
```
docker build -f Dockerfile.local -t strively-dev .
```

3. Rodar o container (porta 8080 externa → 80 interna do Apache):
```
docker run --rm -p 8080:80 --env-file .env strively-dev
```

Acesse em: http://localhost:8080

> Nota: O Strava OAuth não funcionará localmente pois o callback está configurado para o domínio de produção. Isso é esperado e aceitável.
