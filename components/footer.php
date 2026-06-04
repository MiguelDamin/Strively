<?php
$ano = date('Y');
?>
<footer style="background: #fafafa; border-top: 1px solid #eee; padding: 40px 24px; font-family: 'Outfit', sans-serif; margin-top: auto; width: 100%; box-sizing: border-box; flex-shrink: 0;">
  <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
      <span style="font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; letter-spacing: 2px; color: #1a1a1a;">STRIVELY</span>
      <span style="color: #999; font-size: 0.9rem;">&copy; <?= $ano ?> - Todos os direitos reservados.</span>
    </div>
    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
      <a href="/pages/termos.php" style="color: #666; font-size: 0.9rem; text-decoration: none; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='#1DB954'" onmouseout="this.style.color='#666'">Termos de Uso</a>
      <a href="/pages/privacidade.php" style="color: #666; font-size: 0.9rem; text-decoration: none; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='#1DB954'" onmouseout="this.style.color='#666'">Privacidade</a>
    </div>
  </div>
</footer>
