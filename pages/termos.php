<?php
$tituloPagina = "Termos de Uso – Strively";
require_once __DIR__ . '/../config/conexao.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/header.php';
?>

<style>
  .termos-container {
    max-width: 820px;
    margin: 64px auto 80px;
    padding: 0 24px;
    font-family: 'Outfit', sans-serif;
    color: #1a1a1a;
    line-height: 1.8;
  }

  .termos-hero {
    border-bottom: 2px solid #eee;
    padding-bottom: 32px;
    margin-bottom: 40px;
  }

  .termos-hero h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(2.8rem, 6vw, 4.2rem);
    letter-spacing: 1px;
    color: #111;
    margin: 0 0 8px 0;
  }

  .termos-hero .updated {
    font-size: 0.85rem;
    color: #888;
  }

  .termos-hero .intro {
    margin-top: 16px;
    font-size: 1.05rem;
    color: #444;
    max-width: 680px;
  }

  .termos-section {
    margin-bottom: 48px;
  }

  .termos-section h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.6rem;
    letter-spacing: 0.5px;
    color: #111;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .termos-section h2 .num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #111;
    color: #fff;
    font-size: 0.9rem;
    border-radius: 6px;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    flex-shrink: 0;
  }

  .termos-section p {
    font-size: 1rem;
    color: #333;
    margin: 0 0 14px 0;
  }

  .termos-section ul {
    margin: 8px 0 14px 0;
    padding-left: 24px;
    color: #333;
  }

  .termos-section ul li {
    margin-bottom: 8px;
    font-size: 1rem;
  }

  .termos-section ul li strong {
    color: #111;
  }

  .termos-callout {
    background: #f7f7f7;
    border-left: 4px solid #111;
    border-radius: 0 8px 8px 0;
    padding: 16px 20px;
    margin: 16px 0;
    font-size: 0.97rem;
    color: #444;
  }

  .termos-callout.warning {
    border-left-color: #e74c3c;
    background: #fff5f5;
    color: #7a2020;
  }

  .termos-callout a {
    color: #111;
    font-weight: 600;
    text-decoration: underline;
  }

  .termos-divider {
    border: none;
    border-top: 1px solid #eee;
    margin: 0 0 48px 0;
  }

  @media (max-width: 600px) {
    .termos-container {
      margin-top: 40px;
    }
  }
</style>

<div class="termos-container">

  <div class="termos-hero">
    <h1>Termos de Uso</h1>
    <span class="updated">Última atualização: junho de 2026</span>
    <p class="intro">
      Leia atentamente os termos abaixo antes de utilizar o Strively. Ao se cadastrar ou continuar utilizando a plataforma,
      você confirma que leu, compreendeu e concorda integralmente com estes Termos de Uso.
    </p>
  </div>

  <!-- 1. O que é o Strively -->
  <div class="termos-section">
    <h2><span class="num">1</span> O que é o Strively e para que serve</h2>
    <p>
      O <strong>Strively</strong> é uma plataforma digital voltada para corredores e treinadores de corrida.
      Nosso objetivo é conectar atletas aos seus treinadores, centralizar o acompanhamento de treinos,
      facilitar a prescrição de planilhas de treinamento e promover a comunidade do esporte.
    </p>
    <p>A plataforma oferece, entre outros, os seguintes recursos:</p>
    <ul>
      <li>Conexão entre corredores e treinadores;</li>
      <li>Prescrição e visualização de treinos personalizados;</li>
      <li>Sincronização de atividades via integração com o Strava;</li>
      <li>Calendário de eventos de corrida;</li>
      <li>Comunidade e perfis públicos de atletas e treinadores.</li>
    </ul>
    <p>
      O Strively <strong>não é</strong> uma plataforma de pagamentos, serviço médico, ou substituto de acompanhamento profissional de saúde.
    </p>
  </div>

  <hr class="termos-divider">

  <!-- 2. O que o usuário pode e não pode fazer -->
  <div class="termos-section">
    <h2><span class="num">2</span> O que o usuário pode e não pode fazer</h2>

    <p><strong>Você pode:</strong></p>
    <ul>
      <li>Criar uma conta pessoal e manter seu perfil atualizado;</li>
      <li>Conectar-se com treinadores e aceitar convites de vínculo;</li>
      <li>Visualizar, registrar e compartilhar seus treinos e atividades;</li>
      <li>Divulgar e participar de eventos de corrida cadastrados na plataforma;</li>
      <li>Integrar sua conta com o Strava para importar atividades;</li>
      <li>Explorar a comunidade, perfis públicos e o feed da plataforma.</li>
    </ul>

    <p><strong>Você não pode:</strong></p>
    <ul>
      <li>Usar a plataforma para fins ilegais, fraudulentos ou que violem direitos de terceiros;</li>
      <li>Criar contas falsas, se passar por outra pessoa ou fornecer informações enganosas;</li>
      <li>Tentar acessar áreas restritas, dados de outros usuários ou infraestrutura do sistema sem autorização;</li>
      <li>Publicar conteúdo ofensivo, discriminatório, com discurso de ódio ou que infrinja direitos autorais;</li>
      <li>Utilizar a plataforma para envio de spam, propagandas não autorizadas ou coleta massiva de dados;</li>
      <li>Reverter engenharia, copiar ou redistribuir o código-fonte ou funcionalidades da plataforma sem permissão expressa.</li>
    </ul>

    <div class="termos-callout">
      <strong>Nota:</strong> Esta seção será expandida. Caso tenha dúvidas sobre uma ação específica permitida ou não permitida,
      entre em contato pelo suporte antes de proceder.
    </div>
  </div>

  <hr class="termos-divider">

  <!-- 3. Integração com o Strava -->
  <div class="termos-section">
    <h2><span class="num">3</span> Integração com o Strava</h2>
    <p>
      O Strively utiliza a <strong>API oficial do Strava</strong> para permitir que você sincronize suas atividades
      com a plataforma. Ao conectar sua conta ao Strava, você autoriza o Strively a acessar determinados dados
      da sua conta Strava conforme as permissões que você concede durante o processo de autorização.
    </p>
    <div class="termos-callout">
      O uso da API do Strava pelo Strively está sujeito aos
      <a href="https://www.strava.com/legal/api" target="_blank" rel="noopener noreferrer">Termos de Serviço da API do Strava</a>.
      Ao utilizar a integração, você também está sujeito aos
      <a href="https://www.strava.com/legal/terms" target="_blank" rel="noopener noreferrer">Termos de Uso do Strava</a>
      e à sua <a href="https://www.strava.com/legal/privacy" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.
    </div>
    <p>
      O Strively não armazena suas credenciais do Strava. Tokens de acesso são mantidos de forma segura e utilizados
      exclusivamente para a sincronização de atividades. Você pode revogar o acesso a qualquer momento diretamente
      nas configurações do Strava.
    </p>
  </div>

  <hr class="termos-divider">

  <!-- 4. Responsabilidades do treinador -->
  <div class="termos-section">
    <h2><span class="num">4</span> Responsabilidades do treinador</h2>
    <p>
      Treinadores cadastrados no Strively são profissionais independentes e assumem <strong>total responsabilidade</strong>
      pelos treinos, planilhas e orientações que prescrevem a seus alunos, incluindo:
    </p>
    <ul>
      <li>A adequação dos treinos à condição física, histórico de saúde e objetivos de cada aluno;</li>
      <li>A veracidade das informações fornecidas em seu perfil profissional (credenciais, certificações, experiência);</li>
      <li>O cumprimento de normas éticas e legais aplicáveis à sua área de atuação;</li>
      <li>Eventuais danos físicos, psicológicos ou financeiros decorrentes de prescrições inadequadas.</li>
    </ul>
    <p>
      O Strively atua exclusivamente como plataforma intermediadora e <strong>não se responsabiliza</strong> pelo conteúdo
      dos treinos prescritos, pela relação profissional entre treinador e aluno, nem por quaisquer consequências
      decorrentes dessas interações.
    </p>
    <div class="termos-callout">
      Treinadores que receberem denúncias de má conduta ou violação de termos poderão ter seus perfis suspensos
      enquanto o caso for analisado.
    </div>
  </div>

  <hr class="termos-divider">

  <!-- 5. Encerramento de contas -->
  <div class="termos-section">
    <h2><span class="num">5</span> Encerramento de contas</h2>
    <p>
      O Strively reserva-se o direito de <strong>suspender ou encerrar</strong>, a qualquer momento e sem aviso prévio,
      contas de usuários que:
    </p>
    <ul>
      <li>Violem quaisquer disposições destes Termos de Uso;</li>
      <li>Utilizem a plataforma de forma fraudulenta, abusiva ou prejudicial a outros usuários;</li>
      <li>Pratiquem atos que possam comprometer a segurança, integridade ou reputação da plataforma;</li>
      <li>Forneçam informações falsas durante o cadastro ou em seu perfil;</li>
      <li>Descumpram obrigações legais aplicáveis à sua atividade na plataforma.</li>
    </ul>
    <div class="termos-callout warning">
      O encerramento de conta implica na perda de acesso a todos os dados associados, incluindo treinos,
      vínculos e histórico. O Strively não garante recuperação de dados após o encerramento por violação de termos.
    </div>
    <p>
      Você também pode encerrar sua própria conta a qualquer momento nas configurações da plataforma.
      Em caso de dúvidas sobre o processo, entre em contato com o suporte.
    </p>
  </div>

  <hr class="termos-divider">

  <!-- 6. Modificações -->
  <div class="termos-section">
    <h2><span class="num">6</span> Modificações dos Termos</h2>
    <p>
      Podemos atualizar estes Termos de Uso a qualquer momento. Alterações significativas serão comunicadas
      diretamente pela interface da plataforma. O uso contínuo do Strively após a publicação de alterações
      constitui aceite dos novos termos.
    </p>
  </div>

</div>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
