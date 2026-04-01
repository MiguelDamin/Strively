# TODO.md - Strively Fixes Complete + Follow-up

✅ Dropdown navbar funcionando (JS corrigido)
✅ Layout eventos centralizado  
✅ Empty state estilizado (🏃 + botão divulgar)

**Eventos não aparecendo? Verifique Supabase:**
```
SELECT id, nome, status, data_evento, created_at 
FROM eventos ORDER BY created_at DESC LIMIT 5;
```
- status deve ser 'ativo'
- data_evento futuro (cleanup deleta < hoje - 2 dias)

**Teste:**
```
http://localhost/Strively/pages/eventos.php  (Ctrl+F5)
```

**Files edited:** header.php, style.css (2x), eventos.php (2x)

