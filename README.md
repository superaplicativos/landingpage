# AMS Empreendimentos + Pininfarina — Landing Page

Landing page de convite exclusivo para o lançamento do primeiro residencial Pininfarina em Florianópolis (Morro das Pedras, Sul da Ilha, acesso privativo à praia), com formulário de envio de documentos e integração ClickSign para assinatura digital.

## O que já está pronto

- Landing page completa em one-page com navegação por âncoras
- Hero com imagem do empreendimento e overlay escuro para legibilidade
- Seções: Sobre, Diferenciais, Localização, Arquitetura, Lazer, Convite e Documentos
- CTA principal para solicitação do convite
- Formulário com dados do cliente e upload de documentos
- Consentimento LGPD no fluxo do convite
- Backend PHP para ClickSign:
  - validação dos campos e anexos
  - criação de signatário
  - criação de documento a partir de template
  - associação do signatário ao documento
  - disparo de notificação de assinatura
  - armazenamento local dos anexos em `php/uploads`
- Resumo executivo e roadmap de SEO avançado em `entrega.txt`

## Estrutura do diretório

- `index.html` — entrada principal
- `index_op_fullscreen_gradient_overlay.html` — versão nomeada do layout
- `assets/` — CSS, JS, libs e imagens do template
- `pininfarina-florianopolis.jpg` — imagem do hero
- `php/clicksign_invite.php` — backend de integração ClickSign
- `entrega.txt` — resumo e melhorias de SEO

## Passo a passo de configuração

### 1) Subir no servidor

- Faça upload de todo o conteúdo do repositório para o diretório público do servidor (ex.: `public_html/`).
- Garanta que o servidor tenha PHP habilitado.

### 2) Configurar variáveis de ambiente

Defina estas variáveis no servidor:

- `CLICKSIGN_ACCESS_TOKEN`
- `CLICKSIGN_TEMPLATE_KEY`
- `CLICKSIGN_BASE_URL` (opcional; padrão `https://app.clicksign.com`)

### 3) Permissões de upload

O backend salva anexos em `php/uploads`. Garanta permissão de escrita para o usuário do servidor web.

### 4) Ajustes no template ClickSign

O template deve ter campos com os seguintes nomes para preenchimento:

- `nome`
- `email`
- `telefone`
- `cpf`
- `cidade`

### 5) Teste do formulário

- Abra a landing no navegador
- Envie um convite com anexos válidos
- Verifique o e-mail do cliente e o documento gerado na ClickSign

## Observações importantes

- Para testes locais do formulário, use um servidor com PHP (Apache/Nginx + PHP).
- Um servidor estático não executa `php/clicksign_invite.php`.
