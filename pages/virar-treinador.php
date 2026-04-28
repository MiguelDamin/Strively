<?php
// ==========================================================
// STRIVELY — pages/virar-treinador.php
// Formulário para corredor solicitar modo treinador
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

// Só corredores podem acessar
if ($_SESSION['perfil'] === 'treinador') {
  header('Location: /index.php');
  exit();
}

// Verifica se já tem solicitação pendente ou reprovada
require_once '../config/conexao.php';

$stmt = $pdo->prepare("SELECT * FROM treinadores WHERE usuario_id = ?");
$stmt->execute([$_SESSION['id']]);
$solicitacao = $stmt->fetch();

unset($only_session);
$tituloPagina = "Modo Treinador";
include '../components/head.php';
include '../components/header.php';
?>

<body>

<section class="auth-section" style="min-height: auto; padding: 60px 24px; align-items: flex-start;">
  <div class="auth-card" style="max-width: 580px;">

    <!-- ÍCONE + TÍTULO -->
    <div style="text-align: center; margin-bottom: 8px;">
      <div style="font-size: 2.5rem; margin-bottom: 8px;">🏋️</div>
      <h1 class="auth-titulo">Modo Treinador</h1>
      <p class="auth-subtitulo">Compartilhe seu conhecimento com a comunidade Strively</p>
    </div>

    <?php if ($solicitacao && $solicitacao['status'] === 'pendente'): ?>
      <!-- ── JÁ TEM SOLICITAÇÃO PENDENTE ── -->
      <div style="background: #fffbea; border: 1px solid #f5d87a; border-radius: 12px; padding: 20px; text-align: center;">
        <div style="font-size: 2rem; margin-bottom: 8px;">⏳</div>
        <h3 style="font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; font-size: 1.4rem; margin-bottom: 6px;">Solicitação em análise</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6;">
          Já recebemos seu pedido! Nossa equipe está analisando suas informações e em breve você receberá uma resposta no seu e-mail.
        </p>
      </div>

    <?php elseif ($solicitacao && $solicitacao['status'] === 'reprovado'): ?>
      <!-- ── REPROVADO — pode reenviar ── -->
      <div style="background: #fff0f0; border: 1px solid #ffcccc; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
        <div style="font-size: 1.5rem; margin-bottom: 6px;">❌</div>
        <h3 style="font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; font-size: 1.2rem; color: #cc0000; margin-bottom: 4px;">Solicitação reprovada</h3>
        <?php if (!empty($solicitacao['motivo_reprovacao'])): ?>
          <p style="font-size: 0.88rem; color: #aa0000; line-height: 1.5;">
            <strong>Motivo:</strong> <?= htmlspecialchars($solicitacao['motivo_reprovacao']) ?>
          </p>
        <?php endif; ?>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">Você pode enviar uma nova solicitação com as correções necessárias.</p>
      </div>
      <!-- cai no formulário abaixo -->
      <?php include __DIR__ . '/../components/_form_treinador.php'; ?>

    <?php else: ?>
      <!-- ── FORMULÁRIO NORMAL ── -->

      <?php if (isset($_GET['erro'])): ?>
        <div class="auth-erro">
          <?php
            $erros = [
              'campos_vazios'  => 'Preencha todos os campos obrigatórios.',
              'sem_diploma'    => 'Envie o diploma ou carteirinha do CREF.',
              'formato_doc'    => 'Formato inválido. Use PDF, JPG ou PNG.',
              'upload_falhou'  => 'Erro no envio do documento. Tente novamente.',
              'ja_solicitado'  => 'Você já possui uma solicitação em andamento.',
            ];
            echo $erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.';
          ?>
        </div>
      <?php endif; ?>

      <!-- BENEFÍCIOS -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 4px 0 20px;">
        <div style="background: var(--bg); border-radius: 10px; padding: 12px; font-size: 0.82rem; color: var(--text-muted); display: flex; gap: 8px; align-items: flex-start;">
          <span>✅</span> Crie treinos personalizados para alunos
        </div>
        <div style="background: var(--bg); border-radius: 10px; padding: 12px; font-size: 0.82rem; color: var(--text-muted); display: flex; gap: 8px; align-items: flex-start;">
          <span>✅</span> Badge de treinador verificado no perfil
        </div>
        <div style="background: var(--bg); border-radius: 10px; padding: 12px; font-size: 0.82rem; color: var(--text-muted); display: flex; gap: 8px; align-items: flex-start;">
          <span>✅</span> Seja encontrado por corredores da região
        </div>
        <div style="background: var(--bg); border-radius: 10px; padding: 12px; font-size: 0.82rem; color: var(--text-muted); display: flex; gap: 8px; align-items: flex-start;">
          <span>✅</span> Acesso ao painel exclusivo de treinador
        </div>
      </div>

      <form action="../actions/action-virar-treinador.php" method="POST" enctype="multipart/form-data" class="auth-form">

        <!-- CREF -->
        <div class="form-grupo">
          <label for="cref">Número do CREF <span style="color: var(--green); font-weight: 700;">*</span></label>
          <input type="text" id="cref" name="cref" placeholder="Ex: 123456-G/SP" required>
          <small class="form-hint">Registro no Conselho Federal de Educação Física</small>
        </div>

        <!-- FACULDADE -->
        <div class="form-grupo">
          <label for="faculdade">Faculdade / Instituição <span style="color: var(--green); font-weight: 700;">*</span></label>
          <input type="text" id="faculdade" name="faculdade" placeholder="Ex: USP — Educação Física" required>
        </div>

        <!-- ASSESSORIA -->
        <div class="form-grupo">
          <label for="assessoria">Nome da Assessoria (opcional)</label>
          <input type="text" id="assessoria" name="assessoria" placeholder="Ex: Assessoria Corredores SP">
          <small class="form-hint">Se você tiver uma assessoria ou empresa associada</small>
        </div>

        <!-- ESPECIALIDADE -->
        <div class="form-grupo">
          <label for="especialidade">Especialidade Principal <span style="color: var(--green); font-weight: 700;">*</span></label>
          <select id="especialidade" name="especialidade" required style="
            background: var(--bg);
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 11px 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.92rem;
            color: var(--text-main);
            outline: none;
            width: 100%;
            transition: border-color 0.2s;
            cursor: pointer;
          ">
            <option value="" disabled selected>Selecione sua especialidade</option>
            <option value="Corrida de Rua">Corrida de Rua</option>
            <option value="Maratona">Maratona</option>
            <option value="Trail Running">Trail Running</option>
            <option value="Triathlon">Triathlon</option>
            <option value="Atletismo">Atletismo</option>
            <option value="Corrida + Musculação">Corrida + Musculação</option>
            <option value="Iniciantes">Iniciantes</option>
            <option value="Outra">Outra</option>
          </select>
        </div>

        <!-- UPLOAD DO DIPLOMA / CREF -->
        <div class="form-grupo">
          <label for="diploma">Comprovante: Diploma ou Carteirinha do CREF <span style="color: var(--green); font-weight: 700;">*</span></label>
          <input type="file" id="diploma" name="diploma" accept=".pdf,.jpg,.jpeg,.png" required>
          <small class="form-hint">PDF, JPG ou PNG — máximo 8MB. Seus dados ficam seguros e são usados apenas para verificação.</small>
        </div>

        <button type="submit" class="btn-primary btn-full" style="margin-top: 8px;">
          Enviar solicitação
        </button>

      </form>

    <?php endif; ?>

  </div>
</section>

</body>
</html>