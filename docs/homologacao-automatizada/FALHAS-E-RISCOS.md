# Falhas e riscos

F1 — P1: GET /deleteLink/{id} excluiu link sintético sem CSRF; [reprodução](evidencias/http.json). Risco CSRF depende de cookies e contexto, não testado cross-site. Substituir por POST/DELETE com CSRF e revisar outras rotas mutáveis.

F2 — P2: axe identificou contraste, imagens sem texto alternativo e controle sem nome em [browser.json](evidencias/browser.json); validar impacto com tecnologia assistiva.

OAuth real, retenção LGPD, selo legado e recuperação integral não comprovados.
