<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Obtém usuário da sessão se existir
$usuarioLogado = $_SESSION['skedd_usuario'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#168fff">
    <title>Skedd: Agenda escolar</title>
    <link rel="icon" type="image/x-icon" href="https://i.ibb.co/ymJC5sNN/Captura-de-tela-2026-05-19-100134-1.webp" id="icone-redondo">
    
    <!-- CSS DO FULLCALENDAR E DA APLICAÇÃO -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">

    <style>
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        #turmaProva { max-width: 100%; }

        .btn-link-action {
            display: block;
            text-align: center;
            background: #168fff;
            color: white !important;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-link-action:hover { background: #1276d1; }

        .btn-github-config {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            margin-top: 10px;
            border-radius: 12px;
            background: #24292e;
            color: #fff !important;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .btn-github-config:hover { background: #000; transform: translateY(-1px); }

        /* Modal de Licença */
        .modal-licenca {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 16px;
            box-sizing: border-box;
        }
        .modal-licenca-content {
            background: var(--card, #fff);
            color: var(--text, #333);
            max-width: 550px;
            width: 100%;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        /* Botão de salvar fixo no mobile quando o formulário é longo */
        @media (max-width: 768px) {
            #btnMarcar {
                position: sticky;
                bottom: 12px;
                z-index: 10;
                box-shadow: 0 4px 20px rgba(22,143,255,0.4) !important;
            }
        }
    </style>

    <!-- BIBLIOTECAS BASE NO HEAD PARA EVITAR ERROS DE CARREGAMENTO -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/pt-br.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/pt-br.js"></script>
    <script src="skedd-store.js"></script>

    <script>
        // Inicialização de Usuário Segura sincronizada com sessão PHP
        (function() {
            var usuarioSessao = <?php echo json_encode($usuarioLogado); ?>;
            if (usuarioSessao) {
                localStorage.setItem('skedd_sessao', JSON.stringify(usuarioSessao));
            }
            var usuario = SkeddStore.getUsuarioAtual();
            if (!usuario) {
                usuario = SkeddStore.garantirUsuarioPadrao();
            }
            window.usuarioLogadoId = usuario.id;
            window.usuarioNivel = usuario.nivel;
            window.usuarioAtualObj = usuario;
        })();
    </script>
</head>
    
<body>

    <header>
        <div class="header-left">
            <img src="https://i.ibb.co/SX9K8y69/Captura-de-tela-2026-05-19-100134-removebg-preview.webp" alt="Logo Skedd" width="75">
            <h1 class="Titulo_header">Skedd</h1>
        </div>

        <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
            <div class="user-info-header" style="text-align: right; display: flex; flex-direction: column; justify-content: center; line-height: 1.2;">
                <span id="nomeUsuarioHeader" style="font-weight: bold; font-size: 15px; color: #ffffff;">Carregando...</span>
                <span id="detalheUsuarioHeader" style="font-size: 12px; color: rgba(255, 255, 255, 0.85);"></span>
            </div>

            <div style="position: relative; display: inline-block;">
                <button class="config-btn" id="btnNotif" style="margin: 0; position: relative;" title="Notificações">🔔<span id="notif-badge" class="notif-badge" style="display: none;">0</span></button>
                <div class="notif-menu" id="notifMenu">
                    <h3>Notificações</h3>
                    <ul id="listaNotificacoes" class="notif-list">
                        <li style="color: var(--secondary); font-style: italic; padding: 10px; text-align: center;">Nenhuma notificação no momento.</li>
                    </ul>
                </div>
            </div>

            <div style="position: relative; display: inline-block;">
                <button class="config-btn" id="btnConfig" style="margin: 0;" title="Configurações">⚙</button>
                <div class="config-menu" id="configMenu">
                    <h3>Configurações</h3>
                    <button class="tema-btn" id="toggleTheme">Alternar Tema</button>

                    <!-- OPÇÃO SOLICITADA: REPOSITÓRIO GITHUB & LICENÇAS -->
                    <a href="https://github.com/gustavo7771/Skedd-Agenda-estudantil-online" target="_blank" rel="noopener noreferrer" class="btn-github-config" title="Acessar o código fonte e licenças no GitHub">
                        <svg height="16" width="16" viewBox="0 0 16 16" fill="white" style="flex-shrink: 0;"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>
                        <span>GitHub & Licenças</span>
                    </a>

                    <button type="button" class="tema-btn" onclick="$('#modalLicenca').css('display', 'flex')" style="margin-top: 8px; font-size: 13px; background: transparent; border: 1px solid var(--border); color: var(--text);">📄 Ver Termos da Licença</button>

                    <div style="margin-top: 15px; border-top: 1px solid var(--border); padding-top: 10px;">
                        <a href="logout.php" style="color: #ff3b30; text-decoration: none; font-weight: bold; display: block; text-align: center; font-size: 13px;">Sair da Conta</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="main-content">

        <!-- PAINEL LATERAL PARA PROFESSOR OU ADMINISTRADOR -->
        <div class="sidebar-left" id="sidebarProfessor" style="display: none;">
            <h2>Gerenciar Avaliações</h2>

            <div id="boxBannerAdmin" style="display: none; background: rgba(22, 143, 255, 0.1); border: 1px solid #168fff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong style="color: #168fff; display: block; margin-bottom: 10px;">Modo Administrador</strong>
                <p style="font-size: 13px; margin: 0 0 10px 0; line-height: 1.4;">Para criar eventos para múltiplas turmas e gerenciar MySQL, utilize o painel admin.</p>
                <a href="admin.php" class="btn-link-action">Ir para o Dashboard Avançado</a>
            </div>

            <label id="selecao">Selecione a Turma <span style="color:#ff3b30; font-weight:bold;">*</span></label>
            <select id="turmaProva" style="width: 100%; padding: 10px; border-radius: 8px; font-size:14px; margin-bottom: 12px; background: var(--bg); color: var(--text); border: 1px solid var(--border);">
                <!-- Preenchido via script.js -->
            </select>

            <label id="labelDisciplinaProva">Disciplina / Matéria <span style="color:#ff3b30; font-weight:bold;">*</span></label>
            <select id="disciplinaProva" style="width: 100%; padding: 10px; border-radius: 8px; font-size:14px; margin-bottom: 15px; background: var(--bg); color: var(--text); border: 1px solid var(--border);">
                <option value="">Selecione uma disciplina...</option>
            </select>

            <hr style="border: 1px solid var(--border); margin: 15px 0;">
            <label>Nome do Evento</label>
            <input type="text" id="nomeProva" placeholder="Nome da Prova ou Evento">
            
            <div id="boxInputAdminNome" style="display: none;">
                <label>Publicar Como (Exibição)</label>
                <input type="text" id="nomeAdmin" placeholder="Ex: Direção, Coordenação...">
            </div>

            <label>Descrição</label>
            <textarea id="descricaoProva" placeholder="Observações..." rows="3"></textarea>

            <label>Data</label>
            <input type="date" id="dataProva">

            <label for="corProva">Cor da marcação</label>
            <label class="custom-color-picker-wrapper" for="corProva" title="Clique para abrir a roda de cores">
                <div class="custom-color-display">
                    <span class="custom-color-bubble" id="corPreviewDot" style="background-color: #168fff;"></span>
                    <div class="custom-color-info">
                        <span class="custom-color-hex" id="corPreviewHex">#168FFF</span>
                        <span class="custom-color-hint">Clique para abrir a roda de cores</span>
                    </div>
                    <span class="custom-color-icon">🎨</span>
                </div>
                <input type="color" id="corProva" value="#168fff" class="custom-color-native-input"
                       oninput="document.getElementById('corPreviewDot').style.backgroundColor = this.value; document.getElementById('corPreviewHex').textContent = this.value.toUpperCase();"
                       onchange="document.getElementById('corPreviewDot').style.backgroundColor = this.value; document.getElementById('corPreviewHex').textContent = this.value.toUpperCase();">
            </label>

            <button id="btnMarcar">Salvar Evento </button>

            <hr style="border: 1px solid var(--border); margin: 20px 0;">
            <h3 style="font-size: 16px; margin-bottom: 10px; color: var(--text); font-weight: 700;">Todas as Suas Provas</h3>
            <div id="listaGerenciarProvas" style="max-height: 250px; overflow-y: auto; padding-right: 5px;">
                <p style="font-style: italic; color: var(--secondary); font-size: 13px;">Carregando avaliações...</p>
            </div>
        </div>

        <!-- PAINEL LATERAL PARA ALUNO -->
        <div class="sidebar-left aluno-view" id="sidebarAluno" style="display: none;">
            <h2>Área do Aluno</h2>
            <p style="margin-bottom: 12px; color: var(--text);">Visualizando a agenda da turma: <strong id="nomeTurmaExibicaoAluno">-</strong></p>
            
            <!-- LISTA DE DISCIPLINAS DA TURMA DO ALUNO -->
            <div id="containerDisciplinasAluno" style="margin-top: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                    <h3 style="margin: 0; font-size: 15px; color: var(--text); font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        📚 <span>Disciplinas da Turma</span>
                    </h3>
                    <span id="badgeQtdDisciplinasAluno" style="font-size: 11.5px; background: rgba(22, 143, 255, 0.15); color: #0284c7; padding: 2px 8px; border-radius: 12px; font-weight: bold;">0</span>
                </div>
                
                <div id="listaDisciplinasAluno" style="max-height: calc(100vh - 250px); overflow-y: auto; padding-right: 4px; display: flex; flex-direction: column; gap: 10px;">
                    <p style="font-style: italic; color: var(--secondary); font-size: 13px; text-align: center; margin: 15px 0;">Carregando disciplinas da turma...</p>
                </div>
            </div>
        </div>

        <!-- CALENDÁRIO CENTRAL -->
        <div class="container-calendar">
            <div id="calendar"></div>
            
            <div class="container-descricao" id="painelDescricao" style="margin-top: 20px; padding: 15px; border: 1px solid var(--border); border-radius: 20px; display: none; background: var(--card); width: 100%; box-sizing: border-box;">
                <h3 style="margin-top: 0; color: var(--text); font-weight: 700;" id="tituloDescricaoProva">Nome da Prova</h3>
                <p id="textoDescricaoProva" style="margin-bottom: 15px; color: var(--text);">A descrição aparecerá aqui.</p>
                <button id="btnDesmarcar" style="padding: 10px 20px; background-color: #ff3b30; color: white; border: none; border-radius: 30px; cursor: pointer; font-weight: bold; transition: 0.3s;">Desmarcar Prova</button>
                <button id="btnEditar" style="padding: 10px 20px; background-color: #168fff; color: white; border: none; border-radius: 30px; cursor: pointer; font-weight: bold; transition: 0.3s; margin-left: 10px;">Editar Prova</button>
            </div>
        </div>

        <!-- BARRA LATERAL DIREITA: AVALIAÇÕES DO DIA -->
        <div class="sidebar-right">
            <h2 id="tituloPainelDireito">Avaliações do Dia</h2>
            <p id="dataSelecionada">Selecione um dia</p>
            <ul id="listaProvas">
                <li class="vazio">Nenhuma avaliação neste dia.</li>
            </ul>
        </div>
    </div>

    <!-- MODAL DE LICENÇAS E REPOSITÓRIO GITHUB -->
    <div id="modalLicenca" class="modal-licenca" onclick="$(this).hide()">
        <div class="modal-licenca-content" onclick="event.stopPropagation()">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 18px; color: var(--text); font-weight: 700;">Licença & Repositório Oficial</h2>
                <button type="button" onclick="$('#modalLicenca').hide()" style="background:none; border:none; font-size: 20px; cursor:pointer; color: var(--text);">&times;</button>
            </div>
            <p style="font-size: 14px; line-height: 1.5;">
                O <strong>Skedd - Agenda Estudantil Online</strong> é um projeto de código aberto distribuído sob a <strong>Licença MIT</strong>.
            </p>
            <div style="background: rgba(0,0,0,0.06); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; line-height: 1.4; margin: 15px 0; border: 1px solid var(--border);">
                MIT License &copy; 2026 Gustavo Favero Bonetti<br><br>
                Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files, to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies...
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="https://github.com/gustavo7771/Skedd-Agenda-estudantil-online" target="_blank" rel="noopener noreferrer" class="btn-github-config" style="display: inline-flex; width: auto; padding: 10px 20px;">
                    Visitar Repositório no GitHub
                </a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container-rodape">
            <p>&copy; 2026 Skedd: Agenda digital escolar. Todos os direitos reservados. | <a href="https://github.com/gustavo7771/Skedd-Agenda-estudantil-online" target="_blank" rel="noopener noreferrer" style="color: #168fff; text-decoration: underline;">GitHub & Licenças</a></p>
        </div>
    </footer>

    <!-- SCRIPT DA APLICAÇÃO -->
    <script src="script.js?v=<?= time() ?>"></script>
</body>
</html>

