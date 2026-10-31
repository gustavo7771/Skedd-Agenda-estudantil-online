<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$usuarioAdmin = $_SESSION['skedd_usuario'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skedd - Painel de Controle Admin (PHP / MySQL)</title>
    <link rel="icon" type="image/x-icon" href="https://i.ibb.co/ymJC5sNN/Captura-de-tela-2026-05-19-100134-1.webp">
    <link rel="stylesheet" href="style.css">
    <style>
        body.admin-body {
            background-color: #141414;
            color: #fff;
            font-family: 'Inter', Arial, sans-serif;
            padding: 20px;
            margin: 0;
            min-height: 100vh;
        }

        .container-admin { max-width: 95%; margin: 0 auto; }
        .box { background: #222; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid #333; }
        h2 { margin-top: 0; color: #168fff; font-size: 22px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; color: #fff; background: #1a1a1a; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #333; vertical-align: middle; }
        th { background-color: #2c2c2c; color: #168fff; font-weight: bold; }
        tr:hover { background-color: #252525; }
        
        select, input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="date"], textarea {
            background: #2a2a2a; color: #fff; border: 1px solid #444; padding: 8px 12px;
            border-radius: 6px; font-size: 14px; outline: none; width: 100%; box-sizing: border-box; margin-bottom: 10px;
        }
        select:focus, input:focus, textarea:focus { border-color: #168fff; }
        
        .btn-salvar { background: #168fff; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s; white-space: nowrap; }
        .btn-salvar:hover { background: #1276d1; }
        .btn-resetar { background: #e0a800; color: #111; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s; white-space: nowrap; }
        .btn-resetar:hover { background: #ffc107; }
        .btn-deletar { background: #c82333; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s; white-space: nowrap; }
        .btn-deletar:hover { background: #dc3545; }
        
        .alerta-sucesso { background: #155724; color: #d4edda; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #c3e6cb; display: none; }
        .alerta-erro { background: #721c24; color: #f8d7da; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #f5c6cb; display: none; }
        
        .tabs-header { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; flex-wrap: wrap; }
        .tab-btn { background: transparent; color: #888; border: none; padding: 10px 18px; font-size: 15px; font-weight: bold; cursor: pointer; border-radius: 8px 8px 0 0; transition: 0.3s; position: relative; }
        .tab-btn:hover { color: #fff; background: rgba(255, 255, 255, 0.05); }
        .tab-btn.active { color: #168fff; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -12px; left: 0; width: 100%; height: 3px; background: #168fff; border-radius: 3px; }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .radio-group { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .radio-group label { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 15px; }

        .btn-github-top {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #24292e;
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            transition: 0.2s;
            border: 1px solid #444;
        }
        .btn-github-top:hover {
            background: #000;
            border-color: #666;
        }

        /* Login Box específico */
        .login-admin-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        .login-box {
            max-width: 380px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: 15px;
            color: black;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .login-box input {
            background: #fff !important;
            color: #333 !important;
            border: 1px solid #ccc !important;
            margin-bottom: 12px;
        }
    </style>
</head>
<body class="admin-body">

    <!-- TELA 1: LOGIN DE ADMINISTRADOR -->
    <div id="secaoLoginAdmin" class="login-admin-wrapper" style="display: none;">
        <div class="login-box">
            <img src="https://i.ibb.co/ymJC5sNN/Captura-de-tela-2026-05-19-100134-1.webp" alt="Logo" width="60" class="icone-redondo">
            <h3 style="margin: 10px 0 5px 0;">Acesso Administrativo</h3>
            <p style="font-size: 13px; color: #666; margin: 0 0 20px 0;">Painel de Gestão Skedd</p>
            
            <div id="erroLoginAdmin" class="alerta-erro" style="margin-bottom: 15px; font-size: 13px;"></div>

            <form id="formAdminLogin">
                <input type="text" id="adminNome" placeholder="Seu Nome Completo" required>
                <input type="text" id="adminCargo" placeholder="Seu Cargo (Ex: Diretor, Coordenador)" required>
                <input type="password" id="adminSenha" placeholder="Código de Acesso Admin (Dica: admin123)" required>
                <button type="submit" class="btn-salvar" style="width: 100%; padding: 12px; font-size: 16px;">Autenticar</button>
            </form>
            <div style="margin-top: 15px;">
                <a href="index.php" style="color: #666; font-size: 13px; text-decoration: none;">&larr; Voltar para a Tela Inicial</a>
            </div>
        </div>
    </div>

    <!-- TELA 2: PAINEL ADMINISTRATIVO PRINCIPAL -->
    <div id="secaoPainelAdmin" class="container-admin" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: #fff;">Painel Administrativo Skedd</h1>
                <p style="margin: 5px 0 0 0; color: #888;">
                    Logado como: <strong id="adminInfoNome" style="color: #168fff;">-</strong> (<span id="adminInfoCargo">-</span>) | 
                    <a href="agenda.php" style="color: #168fff; text-decoration: none; font-weight: bold;">Ir para Agenda</a> |
                    <a href="logout.php" id="btnSairAdmin" style="color: #ff4d4f; text-decoration: none; font-weight: bold;">Sair</a>
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <!-- LINK DIRETO REPOSITÓRIO GITHUB & LICENÇAS -->
                <a href="https://github.com/gustavo7771/Skedd-Agenda-estudantil-online" target="_blank" rel="noopener noreferrer" class="btn-github-top" title="Abrir o repositório oficial no GitHub">
                    <svg height="16" width="16" viewBox="0 0 16 16" fill="white"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>
                    <span>GitHub & Licenças</span>
                </a>
                <button type="button" id="btnRestaurarPadrao" class="btn-resetar" style="font-size: 12px;" title="Restaura os dados originais no MySQL">🔄 Restaurar Padrões de Fábrica</button>
            </div>
        </div>

        <div id="adminAlertaSucesso" class="alerta-sucesso"></div>
        <div id="adminAlertaErro" class="alerta-erro"></div>

        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab('turmas', this)">📚 Gerenciar Turmas</button>
            <button class="tab-btn" onclick="switchTab('eventos', this)">📅 Gerenciar Eventos</button>
            <button class="tab-btn" onclick="switchTab('usuarios', this)">👥 Gerenciar Usuários</button>
            <button class="tab-btn" onclick="switchTab('disciplinas', this)">📖 Disciplinas</button>
            <button class="tab-btn" onclick="switchTab('questoes', this)">🏛️ Questões Administrativas</button>
            <button class="tab-btn" onclick="switchTab('log', this)">📋 Logs do Sistema</button>
        </div>

        <!-- ABA 1: GERENCIAR TURMAS -->
        <div id="tab-turmas" class="tab-content active">
            <div class="box">
                <h2 style="border:none;">Gerenciamento de Turmas e Apelidos (MySQL)</h2>
                <form id="formAdicionarTurma" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="text" id="novoCodigoTurma" placeholder="Código da turma (Ex: 4001)" required style="width: 200px;">
                    <input type="text" id="novoApelidoTurma" placeholder="Apelido/Nome da Turma (Ex: 4° tec.info)" style="flex: 1; min-width: 250px;">
                    <button type="submit" class="btn-salvar" style="background: #28a745; font-size: 14px;">+ Adicionar Turma</button>
                </form>
                
                <div id="listaTurmasAdmin" style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Renderizado dinamicamente -->
                </div>
            </div>
        </div>

        <!-- ABA 2: GERENCIAR EVENTOS -->
        <div id="tab-eventos" class="tab-content">
            <div class="box">
                <h2 style="border:none;">Criar Nova Avaliação / Evento (MySQL)</h2>
                <form id="formAdminCriarEvento" style="background: #1a1a1a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 2; min-width: 250px;">
                            <label>Nome do Evento</label>
                            <input type="text" id="evNome" placeholder="Ex: Prova de Matemática" required>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label>Data</label>
                            <input type="date" id="evData" required>
                        </div>
                        <div style="flex: 0.5; min-width: 100px;">
                            <label>Cor</label>
                            <input type="color" id="evCor" value="#168fff" style="padding: 2px; height: 38px;">
                        </div>
                    </div>
                    
                    <label>Descrição / Observações</label>
                    <textarea id="evDesc" placeholder="Detalhes opcionais..." rows="3"></textarea>

                    <label style="display: block; margin: 15px 0 5px 0; font-weight: bold; color: #168fff;">Público Alvo (Turmas):</label>
                    <div class="radio-group">
                        <label><input type="radio" name="tipo_alvo" value="todas" checked> Todas as Turmas (Global)</label>
                        <label><input type="radio" name="tipo_alvo" value="uma"> Apenas Uma Turma</label>
                        <label><input type="radio" name="tipo_alvo" value="multiplas"> Múltiplas Turmas Específicas</label>
                    </div>

                    <div id="box-turma-unica" style="display: none; margin-bottom: 15px;">
                        <select id="selectTurmaUnica">
                            <!-- Preenchido dinamicamente -->
                        </select>
                    </div>

                    <div id="box-turmas-multiplas" style="display: none; margin-bottom: 15px; max-height: 150px; overflow-y: auto; background: #222; padding: 15px; border: 1px solid #444; border-radius: 6px;">
                        <!-- Checkboxes preenchidas dinamicamente -->
                    </div>

                    <button type="submit" class="btn-salvar" style="padding: 12px 20px; font-size: 15px;">Salvar Evento no Banco</button>
                </form>

                <h2 style="border:none; margin-top: 40px;">Eventos Ativos no Sistema</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Título do Evento</th>
                                <th>Turma(s) Alvo</th>
                                <th>Autor (Admin/Prof)</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaEventosAdmin">
                            <!-- Preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 3: GERENCIAR USUÁRIOS -->
        <div id="tab-usuarios" class="tab-content">
            <div class="box">
                <h2 style="border:none;">Gerenciamento de Usuários (Criptografia Segura Argon2id / Bcrypt)</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome Completo</th>
                                <th>E-mail</th>
                                <th>Nível</th>
                                <th>Turma Vinculada</th>
                                <th>Turmas Ministradas (Professores)</th>
                                <th style="width: 290px; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaUsuariosAdmin">
                            <!-- Preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 4: GERENCIAR DISCIPLINAS -->
        <div id="tab-disciplinas" class="tab-content">
            <div class="box">
                <h2 style="border:none;">Gerenciamento de Disciplinas</h2>
                <form id="formAdicionarDisciplina" style="background: #1a1a1a; padding: 20px; border-radius: 8px; border: 1px solid #333; margin-bottom: 25px;">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 150px;">
                            <label>Código da Matéria</label>
                            <input type="text" id="discCodigo" placeholder="Ex: MAT1, WEB1">
                        </div>
                        <div style="flex: 2; min-width: 250px;">
                            <label>Nome da Disciplina</label>
                            <input type="text" id="discNome" placeholder="Ex: Programação Web" required>
                        </div>
                        <div style="flex: 1; min-width: 120px;">
                            <label>Carga Horária (h)</label>
                            <input type="number" id="discCarga" placeholder="Ex: 80" value="60">
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label>Turma Vinculada</label>
                            <select id="discTurma">
                                <!-- Preenchido dinamicamente -->
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label>Professor Responsável</label>
                            <select id="discProfessor">
                                <!-- Preenchido dinamicamente -->
                            </select>
                        </div>
                    </div>
                    <label>Ementa / Descrição</label>
                    <textarea id="discDescricao" placeholder="Objetivos e tópicos da matéria..." rows="2"></textarea>
                    <button type="submit" class="btn-salvar" style="padding: 10px 18px;">+ Salvar Disciplina no MySQL</button>
                </form>

                <h3 style="color: #168fff;">Disciplinas Cadastradas</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Carga Horária</th>
                                <th>Turma</th>
                                <th>Professor</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaDisciplinasAdmin">
                            <!-- Preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 5: QUESTÕES ADMINISTRATIVAS -->
        <div id="tab-questoes" class="tab-content">
            <div class="box">
                <h2 style="border:none;">Registro de Questões e Comunicados Administrativos</h2>
                <form id="formAdicionarQuestao" style="background: #1a1a1a; padding: 20px; border-radius: 8px; border: 1px solid #333; margin-bottom: 25px;">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 2; min-width: 250px;">
                            <label>Título da Questão / Chamado</label>
                            <input type="text" id="qTitulo" placeholder="Ex: Liberação de salas, Manutenção, Comunicado Geral" required>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label>Tipo</label>
                            <select id="qTipo">
                                <option value="comunicado">Comunicado Geral</option>
                                <option value="aviso">Aviso Institucional</option>
                                <option value="solicitacao">Solicitação Administrativa</option>
                                <option value="manutencao">Manutenção / TI</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label>Prioridade</label>
                            <select id="qPrioridade">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                    </div>
                    <label>Descrição do Problema ou Despacho Administrativo</label>
                    <textarea id="qDescricao" placeholder="Descreva os detalhes da questão administrativa..." rows="3" required></textarea>
                    <button type="submit" class="btn-salvar" style="padding: 10px 18px;">Publicar Questão Administrativa</button>
                </form>

                <h3 style="color: #168fff;">Histórico de Questões e Chamados</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <th>Autor</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaQuestoesAdmin">
                            <!-- Preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 6: LOGS DO SISTEMA -->
        <div id="tab-log" class="tab-content">
            <div class="box">
                <h2 style="border:none;">Histórico de Ações e Logs do Sistema (MySQL)</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Ação</th>
                                <th>Detalhes</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaLogsAdmin">
                            <!-- Preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- SCRIPTS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="skedd-store.js"></script>
    <script>
        function exibirSucesso(msg) {
            $('#adminAlertaErro').hide();
            $('#adminAlertaSucesso').text('✔️ ' + msg).slideDown().delay(4000).slideUp();
        }

        function exibirErro(msg) {
            $('#adminAlertaSucesso').hide();
            $('#adminAlertaErro').text('⚠️ ' + msg).slideDown().delay(4000).slideUp();
        }

        // Troca de Abas
        window.switchTab = function(tabName, element) {
            $('.tab-content').removeClass('active');
            $('.tab-btn').removeClass('active');
            $('#tab-' + tabName).addClass('active');
            $(element).addClass('active');
        };

        // Controle dos rádios de turma alvo
        $('input[name="tipo_alvo"]').change(function() {
            var val = $(this).val();
            $('#box-turma-unica').hide();
            $('#box-turmas-multiplas').hide();
            
            if (val === 'uma') {
                $('#box-turma-unica').show();
            } else if (val === 'multiplas') {
                $('#box-turmas-multiplas').show();
            }
        });

        // ==========================================
        // RENDERIZAÇÃO DAS TELAS E TABELAS
        // ==========================================
        function atualizarTudo() {
            renderizarCabecalho();
            renderizarTurmas();
            renderizarEventos();
            renderizarUsuarios();
            renderizarDisciplinas();
            renderizarQuestoes();
            renderizarLogs();
        }

        function renderizarCabecalho() {
            var usuario = SkeddStore.getUsuarioAtual();
            if (usuario) {
                $('#adminInfoNome').text(usuario.nome || 'Administrador');
                $('#adminInfoCargo').text(usuario.cargo || usuario.admin_cargo || 'Gestão');
            }
        }

        // 1. RENDERIZAR TURMAS
        function renderizarTurmas() {
            var turmas = SkeddStore.getTurmas();
            var contagem = SkeddStore.getContagemUsuarios().porTurma || {};
            var container = $('#listaTurmasAdmin');
            container.empty();

            var optionsSelect = '<option value="" disabled selected>Selecione a turma...</option>';
            var checkboxesMultiplas = '';
            var optionsSelectDisc = '<option value="">Sem turma específica</option>';

            for (var codigo in turmas) {
                var apelido = turmas[codigo];
                var qtd = contagem[codigo] || 0;

                optionsSelect += `<option value="${codigo}">${codigo} - ${apelido}</option>`;
                optionsSelectDisc += `<option value="${codigo}">${codigo} - ${apelido}</option>`;
                checkboxesMultiplas += `
                    <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                        <input type="checkbox" name="turmas_multiplas[]" value="${codigo}"> 
                        ${codigo} - ${apelido}
                    </label>
                `;

                var itemHtml = `
                    <div style="background: #2c2c2c; padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 15px; border: 1px solid #444; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 180px;">
                            <span style="font-size: 18px; font-weight: bold; color: #168fff;">${codigo}</span>
                            <span style="background: #444; padding: 4px 10px; border-radius: 6px; font-size: 12px; color: #fff; font-weight: bold;">
                                ${qtd} Usuário(s)
                            </span>
                        </div>
                        
                        <div style="margin: 0; display: flex; gap: 8px; flex: 1; min-width: 250px;">
                            <input type="text" id="apelido_${codigo}" value="${apelido}" required style="flex: 1; padding: 8px; font-size: 14px; margin-bottom: 0;" placeholder="Nome da Turma">
                            <button type="button" class="btn-salvar" onclick="salvarApelido('${codigo}')" style="padding: 8px 12px;">✏️ Salvar</button>
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn-deletar" onclick="excluirTurma('${codigo}')" style="padding: 8px 12px;">🗑️ Excluir</button>
                        </div>
                    </div>
                `;
                container.append(itemHtml);
            }

            $('#selectTurmaUnica').html(optionsSelect);
            $('#box-turmas-multiplas').html(checkboxesMultiplas);
            $('#discTurma').html(optionsSelectDisc);
        }

        window.salvarApelido = function(codigo) {
            var novoApelido = $('#apelido_' + codigo).val();
            var res = SkeddStore.editarTurma(codigo, novoApelido);
            if (res.success) {
                exibirSucesso("Apelido da turma " + codigo + " atualizado!");
                atualizarTudo();
            } else {
                exibirErro(res.message);
            }
        };

        window.excluirTurma = function(codigo) {
            if (confirm("Tem certeza que deseja excluir a turma " + codigo + "?")) {
                var res = SkeddStore.excluirTurma(codigo);
                if (res.success) {
                    exibirSucesso("Turma excluída com sucesso!");
                    atualizarTudo();
                } else {
                    exibirErro(res.message);
                }
            }
        };

        $('#formAdicionarTurma').submit(function(e) {
            e.preventDefault();
            var cod = $('#novoCodigoTurma').val();
            var apelido = $('#novoApelidoTurma').val();

            var res = SkeddStore.adicionarTurma(cod, apelido);
            if (res.success) {
                exibirSucesso("Turma " + cod + " criada com sucesso!");
                $('#novoCodigoTurma').val('');
                $('#novoApelidoTurma').val('');
                atualizarTudo();
            } else {
                exibirErro(res.message);
            }
        });

        // 2. RENDERIZAR EVENTOS
        function renderizarEventos() {
            var provas = SkeddStore.getProvas().slice();
            provas.sort(function(a, b) {
                return new Date(a.start) - new Date(b.start);
            });

            var tbody = $('#tabelaEventosAdmin');
            tbody.empty();

            if (provas.length === 0) {
                tbody.append('<tr><td colspan="5" style="text-align: center; color: #888; padding: 20px;">Nenhum evento registrado no MySQL.</td></tr>');
                return;
            }

            provas.forEach(function(p) {
                var dataFormatada = p.start;
                if (p.start.indexOf('-') !== -1) {
                    var partes = p.start.split('-');
                    if (partes.length === 3) {
                        dataFormatada = partes[2] + '/' + partes[1] + '/' + partes[0];
                    }
                }

                var tr = `
                    <tr>
                        <td style="font-weight: bold; color: #168fff;">${dataFormatada}</td>
                        <td>
                            <strong>${p.title}</strong>
                            ${p.description ? `<div style="font-size: 12px; color: #888; margin-top: 4px;">${p.description}</div>` : ''}
                        </td>
                        <td>${p.turma === 'todas' ? 'Global (Todas)' : p.turma}</td>
                        <td style="font-size: 13px;">
                            ${p.nome_admin || (p.criador_id === 'admin_panel' ? 'Admin' : 'Professor')}
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-deletar" onclick="excluirEvento('${p.id}')">🗑️ Excluir</button>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        window.excluirEvento = function(id) {
            if (confirm("Tem certeza que deseja excluir permanentemente este evento do MySQL?")) {
                var res = SkeddStore.deletarProva(id);
                if (res.success) {
                    exibirSucesso("Evento excluído com sucesso!");
                    atualizarTudo();
                } else {
                    exibirErro(res.message);
                }
            }
        };

        $('#formAdminCriarEvento').submit(function(e) {
            e.preventDefault();
            var nome = $('#evNome').val();
            var data = $('#evData').val();
            var cor = $('#evCor').val();
            var desc = $('#evDesc').val();
            var tipoAlvo = $('input[name="tipo_alvo"]:checked').val();

            var alvos = [];
            if (tipoAlvo === 'todas') {
                alvos = ['todas'];
            } else if (tipoAlvo === 'uma') {
                var turmaU = $('#selectTurmaUnica').val();
                if (!turmaU) {
                    exibirErro("Selecione uma turma.");
                    return;
                }
                alvos = [turmaU];
            } else if (tipoAlvo === 'multiplas') {
                $('input[name="turmas_multiplas[]"]:checked').each(function() {
                    alvos.push($(this).val());
                });
                if (alvos.length === 0) {
                    exibirErro("Selecione pelo menos uma turma.");
                    return;
                }
            }

            var usuario = SkeddStore.getUsuarioAtual();
            var nomeAdminFormatado = (usuario.cargo || usuario.admin_cargo || 'Admin') + ' - ' + (usuario.nome || 'Direção');

            for (var i = 0; i < alvos.length; i++) {
                SkeddStore.salvarProva({
                    nome: nome,
                    data: data,
                    cor: cor,
                    descricao: desc,
                    turma: alvos[i],
                    nome_admin: nomeAdminFormatado
                });
            }

            exibirSucesso("Evento(s) gravado(s) com sucesso no MySQL!");
            $('#evNome').val('');
            $('#evDesc').val('');
            atualizarTudo();
        });

        // 3. RENDERIZAR USUÁRIOS
        function renderizarUsuarios() {
            var usuarios = SkeddStore.getUsuarios();
            var turmas = SkeddStore.getTurmas();
            var tbody = $('#tabelaUsuariosAdmin');
            tbody.empty();

            var profOptions = '<option value="">Sem professor definido</option>';

            usuarios.forEach(function(u) {
                if (u.nivel === 'professor') {
                    profOptions += `<option value="${u.id}">${u.nome}</option>`;
                }

                var isProf = (u.nivel === 'professor');
                var optionsTurma = `<option value="" ${(!u.turma || u.turma.indexOf('Docente') !== -1) ? 'selected' : ''}>${isProf ? 'Docente (Múltiplas turmas)' : 'Nenhuma'}</option>`;
                var checkboxesTurmasProf = '';

                var rawTurmas = u.turmas || [];
                if (typeof rawTurmas === 'string') {
                    try { rawTurmas = JSON.parse(rawTurmas); } catch(e) { rawTurmas = []; }
                }
                var userTurmasStr = Array.isArray(rawTurmas) ? rawTurmas.map(String) : [];

                for (var codigo in turmas) {
                    var nomeTurma = turmas[codigo] || codigo;
                    var isSelected = (String(u.turma) === String(codigo)) ? 'selected' : '';
                    optionsTurma += `<option value="${codigo}" ${isSelected}>${codigo} - ${nomeTurma}</option>`;

                    var isChecked = userTurmasStr.includes(String(codigo)) ? 'checked' : '';
                    checkboxesTurmasProf += `
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; cursor:pointer; padding:2px 0; color:#ddd;">
                            <input type="checkbox" class="chk-prof-${u.id}" value="${codigo}" ${isChecked} ${!isProf ? 'disabled' : ''}>
                            <span><strong style="color:#168fff;">${codigo}</strong> - ${nomeTurma}</span>
                        </label>
                    `;
                }

                var badgeDisciplinas = '';
                if (isProf && u.disciplinas && u.disciplinas.length > 0) {
                    badgeDisciplinas = `
                        <div style="margin-top:6px; font-size:11px; color:#9cdcfe; border-top:1px dashed #444; padding-top:4px;">
                            <strong>Matérias na classe:</strong><br>
                            ${u.disciplinas.map(function(d){ return '• ' + d.disciplina_nome + ' (' + (d.turma_nome || d.turma_codigo || 'Geral') + ')'; }).join('<br>')}
                        </div>
                    `;
                }

                var painelTurmasProf = `
                    <div id="box-prof-container-${u.id}" style="${!isProf ? 'opacity:0.4;' : ''}">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:11px; color:#888;">${isProf ? 'Turmas atribuídas:' : 'Apenas p/ professor'}</span>
                            ${isProf ? `
                                <div>
                                    <a href="javascript:void(0)" onclick="$('.chk-prof-${u.id}').prop('checked', true)" style="font-size:11px; color:#168fff; margin-right:6px; text-decoration:none;">Todas</a>
                                    <a href="javascript:void(0)" onclick="$('.chk-prof-${u.id}').prop('checked', false)" style="font-size:11px; color:#ff5555; text-decoration:none;">Limpar</a>
                                </div>
                            ` : ''}
                        </div>
                        <div style="max-height: 105px; min-width: 220px; overflow-y: auto; border: 1px solid #444; padding: 6px; background:#1a1a1a; border-radius:4px;">
                            ${checkboxesTurmasProf}
                        </div>
                        ${badgeDisciplinas}
                    </div>
                `;

                var tr = `
                    <tr>
                        <td>
                            <strong style="color: #168fff; display:block;">${u.nome || 'Não informado'}</strong>
                            <small style="color: #777;">Matrícula: ${u.matricula || '-'}</small>
                        </td>
                        <td>
                            <input type="email" id="email_${u.id}" value="${u.email || ''}" required style="font-size: 13px; margin: 0; min-width:180px;">
                        </td>
                        <td>
                            <select id="nivel_${u.id}" onchange="alternarVisaoNivel('${u.id}', this.value)" style="margin: 0; width:130px;">
                                <option value="aluno" ${u.nivel === 'aluno' ? 'selected' : ''}>Aluno</option>
                                <option value="representante" ${u.nivel === 'representante' ? 'selected' : ''}>Representante</option>
                                <option value="professor" ${u.nivel === 'professor' ? 'selected' : ''}>Professor</option>
                            </select>
                        </td>
                        <td>
                            <select id="turma_${u.id}" style="margin: 0; min-width:160px;" ${isProf ? 'disabled title="Professores têm turmas atribuídas na coluna ao lado"' : ''}>
                                ${optionsTurma}
                            </select>
                        </td>
                        <td>
                            ${painelTurmasProf}
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button type="button" class="btn-salvar" onclick="salvarUsuarioLinha('${u.id}')">💾 Salvar</button>
                            <button type="button" class="btn-resetar" onclick="$('#senha_${u.id}').toggle().focus();" title="Alterar senha com criptografia">🔑</button>
                            <input type="password" id="senha_${u.id}" placeholder="Nova senha" style="display: none; width: 90px; margin: 0 4px; padding: 6px;">
                            <button type="button" class="btn-deletar" onclick="excluirUsuarioLinha('${u.id}')">🗑️</button>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });

            $('#discProfessor').html(profOptions);
        }

        window.alternarVisaoNivel = function(id, novoNivel) {
            var isProf = (novoNivel === 'professor');
            var selectTurma = $('#turma_' + id);
            var boxProf = $('#box-prof-container-' + id);
            var chks = $('.chk-prof-' + id);

            if (isProf) {
                selectTurma.prop('disabled', true);
                boxProf.css('opacity', '1');
                chks.prop('disabled', false);
            } else {
                selectTurma.prop('disabled', false);
                boxProf.css('opacity', '0.4');
                chks.prop('disabled', true);
            }
        };

        window.salvarUsuarioLinha = function(id) {
            var email = $('#email_' + id).val();
            var nivel = $('#nivel_' + id).val();
            var turma = $('#turma_' + id).val();
            var novaSenha = $('#senha_' + id).val();

            var turmasProf = [];
            $('.chk-prof-' + id + ':checked').each(function() {
                turmasProf.push(String($(this).val()));
            });

            var res = SkeddStore.salvarUsuario(id, {
                email: email,
                nivel: nivel,
                turma: turma,
                turmas_prof: turmasProf,
                nova_senha: novaSenha
            });

            if (res && res.success) {
                exibirSucesso("Usuário e turmas atualizados com sucesso no MySQL!");
                atualizarTudo();
            } else {
                exibirErro(res ? res.message : "Erro ao salvar alterações no MySQL.");
            }
        };

        window.excluirUsuarioLinha = function(id) {
            if (confirm("Deseja realmente deletar este usuário do banco de dados?")) {
                var res = SkeddStore.deletarUsuario(id);
                if (res.success) {
                    exibirSucesso("Usuário removido!");
                    atualizarTudo();
                } else {
                    exibirErro(res.message);
                }
            }
        };

        // 4. RENDERIZAR DISCIPLINAS
        function renderizarDisciplinas() {
            var disciplinas = SkeddStore.getDisciplinas();
            var tbody = $('#tabelaDisciplinasAdmin');
            tbody.empty();

            if (disciplinas.length === 0) {
                tbody.append('<tr><td colspan="6" style="text-align:center; color:#888; padding:15px;">Nenhuma disciplina cadastrada.</td></tr>');
                return;
            }

            disciplinas.forEach(function(d) {
                var tr = `
                    <tr>
                        <td style="font-weight:bold; color:#168fff;">${d.codigo || '-'}</td>
                        <td>
                            <strong>${d.nome}</strong>
                            ${d.descricao ? `<div style="font-size:12px; color:#888;">${d.descricao}</div>` : ''}
                        </td>
                        <td>${d.carga_horaria || 0} horas</td>
                        <td>${d.turma_nome || d.turma_codigo || 'Todas / Geral'}</td>
                        <td>${d.professor_nome || '-'}</td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-deletar" onclick="excluirDisciplinaLinha(${d.id})">🗑️ Excluir</button>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        $('#formAdicionarDisciplina').submit(function(e) {
            e.preventDefault();
            var dados = {
                codigo: $('#discCodigo').val(),
                nome: $('#discNome').val(),
                carga_horaria: $('#discCarga').val(),
                turma_codigo: $('#discTurma').val(),
                professor_id: $('#discProfessor').val(),
                descricao: $('#discDescricao').val()
            };
            var res = SkeddStore.salvarDisciplina(dados);
            if (res.success) {
                exibirSucesso("Disciplina salva no MySQL!");
                $('#discCodigo').val('');
                $('#discNome').val('');
                $('#discDescricao').val('');
                atualizarTudo();
            } else {
                exibirErro(res.message);
            }
        });

        window.excluirDisciplinaLinha = function(id) {
            if (confirm("Deseja realmente excluir esta disciplina?")) {
                var res = SkeddStore.excluirDisciplina(id);
                if (res.success) {
                    exibirSucesso("Disciplina excluída!");
                    atualizarTudo();
                } else {
                    exibirErro(res.message);
                }
            }
        };

        // 5. RENDERIZAR QUESTÕES ADMINISTRATIVAS
        function renderizarQuestoes() {
            var questoes = SkeddStore.getQuestoesAdministrativas();
            var tbody = $('#tabelaQuestoesAdmin');
            tbody.empty();

            if (questoes.length === 0) {
                tbody.append('<tr><td colspan="7" style="text-align:center; color:#888; padding:15px;">Nenhuma questão administrativa registrada.</td></tr>');
                return;
            }

            questoes.forEach(function(q) {
                var corBadge = '#28a745';
                if (q.status === 'aberta') corBadge = '#ff9500';
                if (q.status === 'em_analise') corBadge = '#168fff';
                if (q.status === 'arquivada') corBadge = '#6c757d';

                var tr = `
                    <tr>
                        <td style="font-size:12px; color:#aaa;">${(q.criado_em || '').substring(0, 16)}</td>
                        <td>
                            <strong>${q.titulo}</strong>
                            <div style="font-size:12px; color:#888; margin-top:3px;">${q.descricao}</div>
                        </td>
                        <td><span style="background:#333; padding:3px 8px; border-radius:4px; font-size:11px;">${q.tipo}</span></td>
                        <td><strong>${q.prioridade}</strong></td>
                        <td><span style="background:${corBadge}; color:#fff; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:bold;">${q.status}</span></td>
                        <td>${q.autor_nome || 'Admin'}</td>
                        <td style="text-align:center; white-space:nowrap;">
                            <button type="button" class="btn-salvar" style="padding:5px 8px; font-size:12px;" onclick="resolverQuestao(${q.id})">✔️ Resolver</button>
                            <button type="button" class="btn-deletar" style="padding:5px 8px; font-size:12px;" onclick="excluirQuestaoLinha(${q.id})">🗑️</button>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        $('#formAdicionarQuestao').submit(function(e) {
            e.preventDefault();
            var dados = {
                titulo: $('#qTitulo').val(),
                tipo: $('#qTipo').val(),
                prioridade: $('#qPrioridade').val(),
                descricao: $('#qDescricao').val()
            };
            var res = SkeddStore.salvarQuestaoAdministrativa(dados);
            if (res.success) {
                exibirSucesso("Questão administrativa registrada no MySQL!");
                $('#qTitulo').val('');
                $('#qDescricao').val('');
                atualizarTudo();
            } else {
                exibirErro(res.message);
            }
        });

        window.resolverQuestao = function(id) {
            var resposta = prompt("Digite a resposta ou parecer administrativo para esta questão:");
            if (resposta !== null) {
                var res = SkeddStore.atualizarStatusQuestao(id, 'resolvida', resposta);
                if (res.success) {
                    exibirSucesso("Questão marcada como resolvida!");
                    atualizarTudo();
                } else {
                    exibirErro(res.message);
                }
            }
        };

        window.excluirQuestaoLinha = function(id) {
            if (confirm("Deseja realmente remover esta questão administrativa?")) {
                var res = SkeddStore.excluirQuestaoAdministrativa(id);
                if (res.success) {
                    exibirSucesso("Questão removida!");
                    atualizarTudo();
                } else {
                    exibirErro(res.message);
                }
            }
        };

        // 6. RENDERIZAR LOGS
        function renderizarLogs() {
            var logs = SkeddStore.getLogs();
            var tbody = $('#tabelaLogsAdmin');
            tbody.empty();

            if (logs.length === 0) {
                tbody.append('<tr><td colspan="4" style="text-align: center;">Nenhum log encontrado.</td></tr>');
                return;
            }

            logs.forEach(function(l) {
                var tr = `
                    <tr>
                        <td style="color: #aaa; font-size: 13px;">${l.data}</td>
                        <td><strong style="color: #168fff;">${l.acao}</strong></td>
                        <td style="font-size: 13px;">${l.detalhes}</td>
                        <td style="font-size: 11px; color:#777;">${l.ip || '127.0.0.1'}</td>
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        // ==========================================
        // INICIALIZAÇÃO DA PÁGINA
        // ==========================================
        function checarAutenticacao() {
            if (SkeddStore.isAdminAutenticado()) {
                $('#secaoLoginAdmin').hide();
                $('#secaoPainelAdmin').show();
                atualizarTudo();
            } else {
                $('#secaoPainelAdmin').hide();
                $('#secaoLoginAdmin').show();
            }
        }

        $('#formAdminLogin').submit(function(e) {
            e.preventDefault();
            var nome = $('#adminNome').val();
            var cargo = $('#adminCargo').val();
            var senha = $('#adminSenha').val();

            var res = SkeddStore.loginAdmin(nome, cargo, senha);
            if (res.success) {
                checarAutenticacao();
            } else {
                $('#erroLoginAdmin').text(res.message).show();
            }
        });

        $('#btnRestaurarPadrao').click(function() {
            if (confirm("Atenção: Isso restaurará turmas, usuários e eventos para os padrões originais no MySQL. Deseja continuar?")) {
                SkeddStore.resetarParaPadrao();
                exibirSucesso("Dados restaurados para os padrões com sucesso!");
                atualizarTudo();
            }
        });

        $(document).ready(function() {
            checarAutenticacao();
        });
    </script>
</body>
</html>

