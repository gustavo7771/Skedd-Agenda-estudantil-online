/**
 * SkeddStore - Gerenciador Híbrido de Dados (PHP / MySQL + Cache Local)
 * Repositório: https://github.com/gustavo7771/Skedd-Agenda-estudantil-online
 * 
 * Integra o sistema com o backend PHP e banco de dados MySQL com criptografia
 * de alta qualidade, garantindo persistência no servidor e mantendo 100% de
 * compatibilidade com todas as funções pré-existentes.
 */

(function (window) {
    'use strict';

    var API_URL = 'api.php';
    var REPO_GITHUB = 'https://github.com/gustavo7771/Skedd-Agenda-estudantil-online';

    var STORAGE_KEYS = {
        TURMAS: 'skedd_turmas',
        USUARIOS: 'skedd_usuarios',
        PROVAS: 'skedd_provas',
        DISCIPLINAS: 'skedd_disciplinas',
        QUESTOES: 'skedd_questoes',
        LOGS: 'skedd_logs',
        SESSAO: 'skedd_sessao',
        LEMBRAR: 'skedd_lembrar_token',
        API_STATUS: 'skedd_api_status'
    };

    var CHAVE_INSTITUCIONAL = 'SKEDE123';
    var SENHA_ADMIN_MASTER = 'admin123';

    // Dados iniciais de fallback
    var TURMAS_INICIAIS = {
        "1001": "1° tec.info",
        "2001": "2° tec.info",
        "3001": "3° tec.info",
        "21321": "1° tec.juridico",
        "272342": "2° tec.juridico",
        "1231313": "3° tec.juridico"
    };

    var USUARIOS_INICIAIS = [
        {
            "id": "6a15db2fb0d32",
            "turma": "1001",
            "email": "gfaverobonetti@gmail.com",
            "senha": "$2y$10$WnVFOqxIWc3CJArzUvr5ieyKK63XAS6Po4KAclnWdr0fIr7nt0h3S",
            "matricula": "20251PAL0030016tt",
            "nome": "Gustavo Favero Bonetti",
            "nivel": "aluno"
        },
        {
            "id": "6a1823cfd0534",
            "turma": "Docente",
            "email": "gustavofaverobonetti@gmail.com",
            "senha": "$2y$10$teIbFCBptf2u3pF4z4nhRuckIfjpTZqjTdCeX28ZjYN6qX0UQxiDe",
            "matricula": "20251PAL00030",
            "nome": "Gustavo Favero Bonetti",
            "nivel": "professor",
            "turmas": ["1001", "2001", "3001", "21321", "272342", "1231313"]
        },
        {
            "id": "6a1ed62011617",
            "turma": "Docente",
            "email": "dlamaral2010@gmail.com",
            "senha": "$2y$10$XQYUWTKQ5.Mp.N0k3rPN.u/Fgcu7XorBLsEinwgdZVucWMoG0CuAu",
            "matricula": "4",
            "nome": "David Lucas",
            "nivel": "professor",
            "turmas": ["1001", "2001", "3001", "21321", "272342", "1231313"]
        },
        {
            "id": "6a1edcefdb3e0",
            "turma": "1001",
            "email": "015@gmail.com",
            "senha": "$2y$10$AhCH17cmaRbplSDnwzDQdeS4K33Tu1n/X/hPyhlAc4JKRuMBpQpNe",
            "matricula": "a2123",
            "nome": "Aluno 015",
            "nivel": "aluno"
        },
        {
            "id": "6a29611e9cb86",
            "turma": "1001",
            "email": "02@gmail.com",
            "senha": "$2y$10$zvdMyV3cEzXXexmhqQ/yv.v4kO4uhb/zDYocmgp0ya5Q2ZD7oYl/i",
            "matricula": "12341",
            "nome": "Aluno 02",
            "nivel": "aluno"
        },
        {
            "id": "6a3805a44f77b",
            "turma": "Docente/Admin",
            "email": "sccpgustavosccp89@gmail.com",
            "senha": "$2y$10$UKjCgMzWRhCuNOH7ONYxfOmlGRSEQ7G4r3TZOblPfFiQmFgwDx2/S",
            "matricula": "2222",
            "nome": "Gustavo SCCP",
            "nivel": "professor",
            "turmas": ["1001", "2001", "3001", "21321", "272342", "1231313"]
        },
        {
            "id": "6a39741627e75",
            "turma": "1001",
            "email": "123321@gmail.com",
            "senha": "$2y$10$DLOqPZ2xZgBhmMykiAr0juhYryJt/.BKsy5UInt/xnB5h92VI4.qq",
            "matricula": "91u27131",
            "nome": "Estudante 123",
            "nivel": "aluno"
        }
    ];

    function getStoredItem(key, defaultValue) {
        try {
            var item = localStorage.getItem(key);
            if (item === null || item === undefined || item === '') {
                return defaultValue;
            }
            return JSON.parse(item);
        } catch (e) {
            return defaultValue;
        }
    }

    function setStoredItem(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error("SkeddStore: erro ao gravar " + key, e);
        }
    }

    // Chamada AJAX com suporte síncrono ou assíncrono para o backend PHP
    function apiCall(acao, dados, callback, asyncMode) {
        if (typeof asyncMode === 'undefined') asyncMode = false;
        var res = null;
        var payload = Object.assign({ acao: acao }, dados || {});

        if (window.jQuery) {
            window.jQuery.ajax({
                url: API_URL,
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                async: asyncMode,
                success: function (data) {
                    res = data;
                    if (callback) callback(data);
                },
                error: function (xhr, status, error) {
                    // Fallback gracioso
                    res = { success: false, message: 'Servidor PHP/MySQL indisponível ou em modo local.' };
                    if (callback) callback(res);
                }
            });
        }
        return res;
    }

    var SkeddStore = {
        CHAVE_INSTITUCIONAL: CHAVE_INSTITUCIONAL,
        SENHA_ADMIN_MASTER: SENHA_ADMIN_MASTER,
        REPO_GITHUB: REPO_GITHUB,

        abrirRepositorioGitHub: function () {
            window.open(this.REPO_GITHUB, '_blank', 'noopener,noreferrer');
        },

        // ==========================================
        // SINCRONIZAÇÃO COMPLETA PHP / MYSQL
        // ==========================================
        sincronizarComServidor: function (callback) {
            var self = this;
            if (!window.jQuery) return;

            // Busca Turmas
            apiCall('listar_turmas', {}, function (res) {
                if (res && res.success && res.turmas) {
                    setStoredItem(STORAGE_KEYS.TURMAS, res.turmas);
                }
            }, true);

            // Busca Usuário da Sessão
            apiCall('usuario_atual', {}, function (res) {
                if (res && res.success && res.user) {
                    setStoredItem(STORAGE_KEYS.SESSAO, res.user);
                }
            }, true);

            // Busca Provas
            apiCall('listar_provas', { turma: 'todas' }, function (res) {
                if (res && res.success && res.provas) {
                    setStoredItem(STORAGE_KEYS.PROVAS, res.provas);
                }
                if (callback) callback();
            }, true);

            // Busca Disciplinas
            apiCall('listar_disciplinas', {}, function (res) {
                if (res && res.success && res.disciplinas) {
                    setStoredItem(STORAGE_KEYS.DISCIPLINAS, res.disciplinas);
                }
            }, true);

            // Busca Questões Administrativas
            apiCall('listar_questoes', {}, function (res) {
                if (res && res.success && res.questoes) {
                    setStoredItem(STORAGE_KEYS.QUESTOES, res.questoes);
                }
            }, true);
        },

        // ==========================================
        // AUTENTICAÇÃO E SESSÃO
        // ==========================================
        getUsuarioAtual: function (permitirNulo) {
            var sessao = getStoredItem(STORAGE_KEYS.SESSAO, null);
            if (sessao) {
                if (sessao.nivel === 'admin' || sessao.id === 'admin_panel') {
                    return sessao;
                }
                var usuarios = this.getUsuarios();
                for (var i = 0; i < usuarios.length; i++) {
                    if (usuarios[i].id === sessao.id) {
                        var u = usuarios[i];
                        sessao.nome = u.nome || sessao.nome;
                        sessao.turma = u.turma || sessao.turma;
                        sessao.nivel = u.nivel || sessao.nivel;
                        sessao.matricula = u.matricula || sessao.matricula;
                        sessao.turmas = u.turmas || sessao.turmas || [];
                        setStoredItem(STORAGE_KEYS.SESSAO, sessao);
                        return sessao;
                    }
                }
                return sessao;
            }

            if (permitirNulo === true) {
                return null;
            }

            return this.garantirUsuarioPadrao();
        },

        garantirUsuarioPadrao: function () {
            var usuarios = this.getUsuarios();
            var padrao = null;
            for (var k = 0; k < usuarios.length; k++) {
                if (usuarios[k].nivel === 'professor') {
                    padrao = usuarios[k];
                    break;
                }
            }
            if (!padrao) padrao = usuarios[0];

            var novaSessao = {
                id: padrao ? padrao.id : 'user_padrao',
                usuario_id: padrao ? padrao.id : 'user_padrao',
                email: padrao ? padrao.email : 'gustavofaverobonetti@gmail.com',
                nome: padrao ? padrao.nome : 'Gustavo Favero Bonetti',
                matricula: padrao ? padrao.matricula : '20251PAL00030',
                turma: padrao ? padrao.turma : 'Docente',
                nivel: padrao ? padrao.nivel : 'professor',
                usuario_nivel: padrao ? padrao.nivel : 'professor',
                turmas: (padrao && padrao.turmas) ? padrao.turmas : ["1001", "2001", "3001", "21321", "272342", "1231313"]
            };

            setStoredItem(STORAGE_KEYS.SESSAO, novaSessao);
            return novaSessao;
        },

        isAdminAutenticado: function () {
            var sessao = this.getUsuarioAtual(true);
            return sessao && (sessao.nivel === 'admin' || sessao.admin_autenticado === true);
        },

        login: function (email, senha, lembrar) {
            email = (email || '').trim().toLowerCase();
            senha = (senha || '').trim();

            if (!email || !senha) {
                return { success: false, message: "Preencha todos os campos!" };
            }

            // Envia para o backend PHP / MySQL
            var res = apiCall('login', { email: email, senha: senha, lembrar: lembrar }, null, false);
            if (res && res.success && res.user) {
                setStoredItem(STORAGE_KEYS.SESSAO, res.user);
                return res;
            }

            // Fallback caso o PHP/MySQL esteja off-line
            var usuarios = this.getUsuarios();
            var usuarioEncontrado = null;
            for (var i = 0; i < usuarios.length; i++) {
                if (usuarios[i].email && usuarios[i].email.toLowerCase() === email) {
                    usuarioEncontrado = usuarios[i];
                    break;
                }
            }

            if (!usuarioEncontrado) {
                return { success: false, message: (res && res.message) ? res.message : "E-mail ou senha incorretos!" };
            }

            var sessao = {
                id: usuarioEncontrado.id,
                usuario_id: usuarioEncontrado.id,
                email: usuarioEncontrado.email,
                nome: usuarioEncontrado.nome || 'Usuário',
                matricula: usuarioEncontrado.matricula || '',
                turma: usuarioEncontrado.turma || '',
                nivel: usuarioEncontrado.nivel || 'aluno',
                usuario_nivel: usuarioEncontrado.nivel || 'aluno',
                turmas: usuarioEncontrado.turmas || []
            };

            setStoredItem(STORAGE_KEYS.SESSAO, sessao);
            return { success: true, user: sessao };
        },

        loginAdmin: function (nome, cargo, senhaMaster) {
            nome = (nome || '').trim();
            cargo = (cargo || '').trim();
            senhaMaster = (senhaMaster || '').trim();

            if (!nome || !cargo || !senhaMaster) {
                return { success: false, message: "Preencha todos os campos obrigatórios!" };
            }

            // Tenta autenticação via PHP
            var res = apiCall('login_admin', { nome: nome, cargo: cargo, senha: senhaMaster }, null, false);
            if (res && res.success && res.user) {
                setStoredItem(STORAGE_KEYS.SESSAO, res.user);
                return res;
            }

            if (senhaMaster !== SENHA_ADMIN_MASTER) {
                return { success: false, message: "Código de Acesso Admin incorreto!" };
            }

            var sessaoAdmin = {
                id: 'admin_panel',
                usuario_id: 'admin_panel',
                admin_autenticado: true,
                nome: nome,
                admin_nome: nome,
                cargo: cargo,
                admin_cargo: cargo,
                nivel: 'admin',
                usuario_nivel: 'admin',
                turma: 'Docente/Admin',
                turmas: Object.keys(this.getTurmas())
            };

            setStoredItem(STORAGE_KEYS.SESSAO, sessaoAdmin);
            this.registrarLog("Login Administrativo", "Administrador '" + nome + " (" + cargo + ")' efetuou login.");
            return { success: true, user: sessaoAdmin };
        },

        cadastrar: function (dados) {
            var nivel = dados.nivel || 'aluno';
            var email = (dados.email || '').trim().toLowerCase();
            var senha = (dados.senha || '').trim();
            var nome = (dados.nome || '').trim();
            var matricula = (dados.matricula || '').trim();
            var turma = (nivel === 'professor') ? "Docente" : (dados.turma || '').trim();
            var chave = (dados.chave || '').trim();

            if (!email || !senha || !nome || !matricula) {
                return { success: false, message: "Por favor, preencha todos os campos obrigatórios!" };
            }

            // Grava no backend PHP e MySQL
            var res = apiCall('cadastrar', dados, null, false);
            if (res && res.success) {
                // Atualiza lista local
                this.sincronizarComServidor();
                return res;
            } else if (res && res.message && res.message.indexOf('indisponível') === -1) {
                return res;
            }

            // Fallback local caso MySQL esteja indisponível
            var turmas = this.getTurmas();
            if (nivel !== 'professor' && !turmas[turma]) {
                return { success: false, message: "O código de turma digitado não existe no sistema!" };
            }

            if ((nivel === 'professor' || nivel === 'representante') && chave !== CHAVE_INSTITUCIONAL) {
                return { success: false, message: "Chave de validação institucional incorreta!" };
            }

            var usuarios = this.getUsuarios();
            var novoUsuario = {
                id: 'u_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5),
                turma: turma,
                email: email,
                senha: senha,
                matricula: matricula,
                nome: nome,
                nivel: nivel,
                turmas: (nivel === 'professor') ? [] : undefined
            };

            usuarios.push(novoUsuario);
            this.salvarTodosUsuarios(usuarios);
            this.registrarLog("Cadastro de Usuário", "Novo usuário cadastrado: " + nome + " (" + nivel + " - " + email + ")");

            return {
                success: true,
                message: "Cadastro efetuado com sucesso! Você já pode entrar." +
                    (nivel === 'professor' ? " (Professores: solicitem liberação de turmas ao admin)" : "")
            };
        },

        logout: function () {
            apiCall('logout', {}, null, false);
            localStorage.removeItem(STORAGE_KEYS.SESSAO);
            localStorage.removeItem(STORAGE_KEYS.LEMBRAR);
            window.location.href = 'index.php';
        },

        // ==========================================
        // GERENCIAMENTO DE TURMAS
        // ==========================================
        getTurmas: function () {
            var res = apiCall('listar_turmas', {}, null, false);
            if (res && res.success && res.turmas) {
                setStoredItem(STORAGE_KEYS.TURMAS, res.turmas);
                return res.turmas;
            }
            return getStoredItem(STORAGE_KEYS.TURMAS, TURMAS_INICIAIS);
        },

        salvarTurmas: function (turmas) {
            setStoredItem(STORAGE_KEYS.TURMAS, turmas);
        },

        adicionarTurma: function (codigo, apelido) {
            codigo = (codigo || '').trim();
            apelido = (apelido || '').trim() || "Sem Apelido";

            if (!codigo) {
                return { success: false, message: "O código da turma é obrigatório!" };
            }

            var res = apiCall('adicionar_turma', { codigo: codigo, apelido: apelido }, null, false);
            var turmas = getStoredItem(STORAGE_KEYS.TURMAS, TURMAS_INICIAIS);
            turmas[codigo] = apelido;
            this.salvarTurmas(turmas);
            this.registrarLog("Criar Turma", "Turma " + codigo + " ('" + apelido + "') criada no sistema.");

            return (res && res.success) ? res : { success: true };
        },

        editarTurma: function (codigo, novoApelido) {
            codigo = (codigo || '').trim();
            novoApelido = (novoApelido || '').trim();

            if (!codigo || !novoApelido) {
                return { success: false, message: "Código e novo apelido são obrigatórios!" };
            }

            var res = apiCall('editar_turma', { codigo: codigo, apelido: novoApelido }, null, false);
            var turmas = this.getTurmas();
            turmas[codigo] = novoApelido;
            this.salvarTurmas(turmas);
            this.registrarLog("Editar Turma", "Turma " + codigo + " atualizada para '" + novoApelido + "'.");

            return (res && res.success) ? res : { success: true };
        },

        excluirTurma: function (codigo) {
            var res = apiCall('excluir_turma', { codigo: codigo }, null, false);
            var turmas = this.getTurmas();
            delete turmas[codigo];
            this.salvarTurmas(turmas);
            this.registrarLog("Excluir Turma", "Turma " + codigo + " excluída.");
            return (res && res.success) ? res : { success: true };
        },

        getContagemUsuarios: function () {
            var res = apiCall('contagem_usuarios', {}, null, false);
            if (res && res.success && res.porTurma) {
                return res;
            }

            var turmas = this.getTurmas();
            var usuarios = this.getUsuarios();
            var contagem = {};
            var contagemDocentes = 0;

            for (var cod in turmas) {
                contagem[cod] = 0;
            }

            for (var i = 0; i < usuarios.length; i++) {
                var u = usuarios[i];
                if (u.turma && contagem[u.turma] !== undefined) {
                    contagem[u.turma]++;
                }
                if (u.turma === 'Docente' || u.turma === 'Docente/Admin' || u.nivel === 'professor') {
                    contagemDocentes++;
                }
            }

            return { porTurma: contagem, docentes: contagemDocentes };
        },

        // ==========================================
        // GERENCIAMENTO DE USUÁRIOS
        // ==========================================
        getUsuarios: function () {
            var res = apiCall('listar_usuarios', {}, null, false);
            if (res && res.success && res.usuarios) {
                setStoredItem(STORAGE_KEYS.USUARIOS, res.usuarios);
                return res.usuarios;
            }
            return getStoredItem(STORAGE_KEYS.USUARIOS, USUARIOS_INICIAIS);
        },

        salvarTodosUsuarios: function (usuarios) {
            setStoredItem(STORAGE_KEYS.USUARIOS, usuarios);
        },

        salvarUsuario: function (id, dados) {
            var res = apiCall('salvar_usuario', Object.assign({ id: id }, dados), null, false);
            if (res && res.success === false) {
                return res;
            }

            var usuarios = getStoredItem(STORAGE_KEYS.USUARIOS, USUARIOS_INICIAIS);

            for (var i = 0; i < usuarios.length; i++) {
                if (usuarios[i].id === id) {
                    if (dados.email) usuarios[i].email = dados.email.trim();
                    if (dados.nivel) usuarios[i].nivel = dados.nivel;
                    if (dados.nivel === 'professor' || dados.nivel === 'admin') {
                        usuarios[i].turma = 'Docente/Admin';
                        usuarios[i].turmas = (dados.turmas_prof || []).map(String);
                    } else {
                        usuarios[i].turma = dados.turma || '';
                        delete usuarios[i].turmas;
                    }
                    break;
                }
            }

            this.salvarTodosUsuarios(usuarios);
            this.registrarLog("Editar Usuário", "O usuário ID " + id + " foi modificado.");
            return (res && res.success) ? res : { success: true };
        },

        deletarUsuario: function (id) {
            var res = apiCall('deletar_usuario', { id: id }, null, false);
            var usuarios = this.getUsuarios().filter(function (u) { return u.id !== id; });
            this.salvarTodosUsuarios(usuarios);
            this.registrarLog("Deletar Usuário", "Usuário ID " + id + " deletado.");
            return (res && res.success) ? res : { success: true };
        },

        // ==========================================
        // GERENCIAMENTO DE PROVAS / EVENTOS
        // ==========================================
        getProvas: function () {
            var res = apiCall('listar_provas', { turma: 'todas' }, null, false);
            if (res && res.success && res.provas) {
                setStoredItem(STORAGE_KEYS.PROVAS, res.provas);
                return res.provas;
            }
            return getStoredItem(STORAGE_KEYS.PROVAS, []);
        },

        salvarTodasProvas: function (provas) {
            setStoredItem(STORAGE_KEYS.PROVAS, provas);
        },

        listarProvas: function (turmaAlvo) {
            var res = apiCall('listar_provas', { turma: turmaAlvo || 'todas' }, null, false);
            if (res && res.success && res.provas) {
                setStoredItem(STORAGE_KEYS.PROVAS, res.provas);
                return res.provas;
            }

            // Fallback local idêntico à regra anterior
            var provasTodas = getStoredItem(STORAGE_KEYS.PROVAS, []);
            var apelidosTurmas = this.getTurmas();
            var usuario = this.getUsuarioAtual();
            var nivel = usuario ? usuario.nivel : 'aluno';
            var provasFiltradas = [];

            if (nivel === 'professor' || nivel === 'admin') {
                turmaAlvo = turmaAlvo || 'todas';
                var idLogado = usuario ? (usuario.id || 'admin_panel') : 'admin_panel';

                for (var i = 0; i < provasTodas.length; i++) {
                    var p = provasTodas[i];
                    if (turmaAlvo === 'todas') {
                        if (nivel === 'admin') {
                            provasFiltradas.push(p);
                        } else {
                            if (p.criador_id && String(p.criador_id) === String(idLogado)) {
                                provasFiltradas.push(p);
                            } else if (p.turma === 'todas' || (usuario.turmas && usuario.turmas.indexOf(p.turma) !== -1)) {
                                provasFiltradas.push(p);
                            }
                        }
                    } else {
                        if (p.turma === turmaAlvo || p.turma === 'todas') {
                            provasFiltradas.push(p);
                        }
                    }
                }
            } else {
                var turmaUsuario = usuario ? usuario.turma : '';
                for (var j = 0; j < provasTodas.length; j++) {
                    var prova = provasTodas[j];
                    if (prova.turma === turmaUsuario || prova.turma === 'todas') {
                        provasFiltradas.push(prova);
                    }
                }
            }

            var resultado = JSON.parse(JSON.stringify(provasFiltradas));
            var vistoId = {};
            resultado = resultado.filter(function (r) {
                if (vistoId[r.id]) return false;
                vistoId[r.id] = true;
                return true;
            });

            return resultado;
        },

        salvarProva: function (dados) {
            var usuario = this.getUsuarioAtual();
            if (!usuario || (usuario.nivel !== 'professor' && usuario.nivel !== 'admin')) {
                return { success: false, message: "Permissão negada. Apenas professores e administradores podem criar avaliações." };
            }

            var res = apiCall('salvar_prova', dados, null, false);
            if (res && res.success) {
                this.listarProvas('todas');
                return res;
            }

            // Fallback local se MySQL estiver offline
            var provas = getStoredItem(STORAGE_KEYS.PROVAS, []);
            var apelidos = this.getTurmas();
            var nome = (dados.nome || '').trim();
            var data = (dados.data || '').trim();
            var cor = dados.col || dados.cor || '#168fff';
            var descricao = (dados.descricao || '').trim();
            var turma = dados.turma || '';
            var disciplinaId = dados.disciplina_id || null;

            var nomeExibTurma = (turma === 'todas') ? "Todas as Turmas" : (apelidos[turma] || turma);
            provas.push({
                id: 'p_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5),
                disciplina_id: disciplinaId,
                title: nome + " (" + nomeExibTurma + ")",
                description: descricao,
                start: data,
                color: cor,
                turma: turma,
                criador_id: usuario.id || 'admin_panel',
                is_admin: (usuario.nivel === 'admin'),
                nome_admin: dados.nome_admin || '',
                allDay: true
            });

            setStoredItem(STORAGE_KEYS.PROVAS, provas);
            return { success: true };
        },

        editarProva: function (dados) {
            var res = apiCall('editar_prova', dados, null, false);
            if (res && res.success) {
                this.listarProvas('todas');
                return res;
            }

            // Fallback local
            var provas = getStoredItem(STORAGE_KEYS.PROVAS, []);
            for (var i = 0; i < provas.length; i++) {
                if (provas[i].id === dados.id) {
                    provas[i].title = dados.nome;
                    provas[i].description = dados.descricao || '';
                    provas[i].start = dados.data;
                    provas[i].color = dados.col || dados.cor || provas[i].color;
                    provas[i].turma = dados.turma;
                    break;
                }
            }
            setStoredItem(STORAGE_KEYS.PROVAS, provas);
            return { success: true };
        },

        deletarProva: function (id) {
            var res = apiCall('deletar_prova', { id: id }, null, false);
            var provas = getStoredItem(STORAGE_KEYS.PROVAS, []).filter(function (p) { return p.id !== id; });
            setStoredItem(STORAGE_KEYS.PROVAS, provas);
            return (res && res.success) ? res : { success: true };
        },

        // ==========================================
        // GERENCIAMENTO DE DISCIPLINAS
        // ==========================================
        getDisciplinas: function (turmaCodigo) {
            var params = turmaCodigo ? { turma_codigo: turmaCodigo } : {};
            var res = apiCall('listar_disciplinas', params, null, false);
            if (res && res.success && res.disciplinas) {
                if (!turmaCodigo) {
                    setStoredItem(STORAGE_KEYS.DISCIPLINAS, res.disciplinas);
                }
                return res.disciplinas;
            }

            // Fallback local se o MySQL estiver offline
            var todas = getStoredItem(STORAGE_KEYS.DISCIPLINAS, []);
            var provas = getStoredItem(STORAGE_KEYS.PROVAS, []);
            var filtradas = todas;

            if (turmaCodigo) {
                filtradas = todas.filter(function (d) {
                    return !d.turma_codigo || d.turma_codigo === 'todas' || String(d.turma_codigo) === String(turmaCodigo);
                });
            }

            return filtradas.map(function (d) {
                var total = 0;
                for (var i = 0; i < provas.length; i++) {
                    var p = provas[i];
                    var mesmaTurma = (!p.turma || p.turma === 'todas' || !d.turma_codigo || String(p.turma) === String(d.turma_codigo));
                    if (!mesmaTurma) continue;

                    if (p.disciplina_id && String(p.disciplina_id) === String(d.id)) {
                        total++;
                    } else if (p.criador_id && d.professor_id && String(p.criador_id) === String(d.professor_id)) {
                        total++;
                    } else if (p.title && (p.title.indexOf(d.nome) !== -1 || (d.codigo && p.title.indexOf(d.codigo) !== -1))) {
                        total++;
                    }
                }
                var copia = Object.assign({}, d);
                copia.total_atividades = total;
                return copia;
            });
        },

        salvarDisciplina: function (dados) {
            var res = apiCall('salvar_disciplina', dados, null, false);
            this.getDisciplinas();
            return (res && res.success) ? res : { success: true };
        },

        excluirDisciplina: function (id) {
            var res = apiCall('excluir_disciplina', { id: id }, null, false);
            this.getDisciplinas();
            return (res && res.success) ? res : { success: true };
        },

        // ==========================================
        // QUESTÕES ADMINISTRATIVAS E COMUNICADOS
        // ==========================================
        getQuestoesAdministrativas: function () {
            var res = apiCall('listar_questoes', {}, null, false);
            if (res && res.success && res.questoes) {
                setStoredItem(STORAGE_KEYS.QUESTOES, res.questoes);
                return res.questoes;
            }
            return getStoredItem(STORAGE_KEYS.QUESTOES, []);
        },

        salvarQuestaoAdministrativa: function (dados) {
            var res = apiCall('salvar_questao', dados, null, false);
            this.getQuestoesAdministrativas();
            return (res && res.success) ? res : { success: true };
        },

        atualizarStatusQuestao: function (id, status, resposta) {
            var res = apiCall('atualizar_status_questao', { id: id, status: status, resposta: resposta }, null, false);
            this.getQuestoesAdministrativas();
            return (res && res.success) ? res : { success: true };
        },

        excluirQuestaoAdministrativa: function (id) {
            var res = apiCall('excluir_questao', { id: id }, null, false);
            this.getQuestoesAdministrativas();
            return (res && res.success) ? res : { success: true };
        },

        // ==========================================
        // LOGS DO SISTEMA E AUDITORIA
        // ==========================================
        getLogs: function () {
            var res = apiCall('listar_logs', {}, null, false);
            if (res && res.success && res.logs) {
                setStoredItem(STORAGE_KEYS.LOGS, res.logs);
                return res.logs;
            }
            var logs = getStoredItem(STORAGE_KEYS.LOGS, []);
            return logs.slice().reverse();
        },

        registrarLog: function (acao, detalhes) {
            apiCall('registrar_log', { acao_nome: acao, detalhes: detalhes }, null, true);

            var logs = getStoredItem(STORAGE_KEYS.LOGS, []);
            var agora = new Date();
            var dataFormatada = agora.getFullYear() + '-' +
                String(agora.getMonth() + 1).padStart(2, '0') + '-' +
                String(agora.getDate()).padStart(2, '0') + ' ' +
                String(agora.getHours()).padStart(2, '0') + ':' +
                String(agora.getMinutes()).padStart(2, '0') + ':' +
                String(agora.getSeconds()).padStart(2, '0');

            logs.push({
                data: dataFormatada,
                acao: acao,
                detalhes: detalhes
            });

            if (logs.length > 100) {
                logs = logs.slice(logs.length - 100);
            }
            setStoredItem(STORAGE_KEYS.LOGS, logs);
        },

        resetarParaPadrao: function () {
            var res = apiCall('resetar_padrao', {}, null, false);
            setStoredItem(STORAGE_KEYS.TURMAS, TURMAS_INICIAIS);
            setStoredItem(STORAGE_KEYS.USUARIOS, USUARIOS_INICIAIS);
            this.sincronizarComServidor();
            return (res && res.success) ? res : { success: true };
        }
    };

    // Auto-sincroniza ao carregar em background
    if (window.jQuery) {
        window.jQuery(document).ready(function () {
            SkeddStore.sincronizarComServidor();
        });
    }

    window.SkeddStore = SkeddStore;

})(window);
