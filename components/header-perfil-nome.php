<?php
// components/header-perfil-nome.php

// Expected variables:
// $header_link_voltar - URL for "Voltar" link (e.g., "/pages/alunos.php")
// $header_label_voltar - Text for "Voltar" link (e.g., "Meus alunos")
// $header_foto - User's profile photo
// $header_nome - User's name
// $header_cidade - User's city
// $header_acoes_html - (Optional) Additional HTML to place to the right side
// $header_foto_link - (Optional) Link to wrap around the photo
?>
<style>
/* CSS scoped for this header */
.p-header-unique {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}
.p-header-top {
    display: flex;
    align-items: center;
}
.p-header-top a.voltar {
    color: var(--text-secondary, #555);
    font-size: .85rem;
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: color .2s;
}
.p-header-top a.voltar:hover {
    color: var(--green);
}
.p-header-main {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.p-header-foto {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid var(--green);
    flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(29,185,84,.1);
}
.p-header-foto-padrao {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #f0f0f0;
    border: 2.5px solid var(--green);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(29,185,84,.1);
}
.p-header-foto-padrao svg {
    width: 28px;
    height: 28px;
    fill: #aaa;
}
.p-header-info {
    flex: 1;
    min-width: 0;
}
.p-header-info h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.9rem;
    letter-spacing: 2px;
    line-height: 1;
    margin: 0 0 4px 0;
    color: var(--text-primary, #111);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.p-header-info span {
    font-size: .82rem;
    color: var(--text-secondary, #555);
    display: block;
}
.p-header-acoes {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
}
@media(max-width: 600px) {
    .p-header-acoes {
        margin-left: 0;
        width: 100%;
        display:flex;
    }
}
</style>

<div class="p-header-unique">
    <?php if (!empty($header_link_voltar)): ?>
    <div class="p-header-top">
        <a href="<?= htmlspecialchars($header_link_voltar) ?>" class="voltar">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            <?= htmlspecialchars($header_label_voltar) ?>
        </a>
    </div>
    <?php endif; ?>
    
    <div class="p-header-main">
        <?php if (!empty($header_foto_link)): ?>
            <a href="<?= htmlspecialchars($header_foto_link) ?>" style="text-decoration:none; display:contents;">
        <?php endif; ?>
        
        <?php if (!empty($header_foto)): ?>
            <img src="<?= htmlspecialchars(strpos($header_foto, 'http') === 0 ? $header_foto : '/' . ltrim($header_foto, '/')) ?>" alt="Foto" class="p-header-foto">
        <?php else: ?>
            <div class="p-header-foto-padrao">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($header_foto_link)): ?>
            </a>
        <?php endif; ?>

        <div class="p-header-info">
            <h1><?= htmlspecialchars($header_nome) ?></h1>
            <?php if (isset($header_cidade) && $header_cidade !== false && $header_cidade !== ''): ?>
            <span><?= htmlspecialchars($header_cidade) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($header_acoes_html)): ?>
            <div class="p-header-acoes">
                <?= $header_acoes_html ?>
            </div>
        <?php endif; ?>
    </div>
</div>
