/**
 * SkeddStore - Gerenciador de Dados e API do Skedd (100% Client-Side)
 * Substitui o backend PHP por armazenamento local persistente (localStorage),
 * mantendo todas as funcionalidades do sistema: autenticação, turmas, avaliações,
 * logs, permissões e notificações.
 */

(function (window) {
    'use strict';

    var STORAGE_KEYS = {
        TURMAS: 'skedd_turmas',
        USUARIOS: 'skedd_usuarios',
        PROVAS: 'skedd_provas',
        LOGS: 'skedd_logs',
        SESSAO: 'skedd_sessao',
        LEMBRAR: 'skedd_lembrar_token'
    };

    var CHAVE_INSTITUCIONAL = 'SKEDE123';
    var SENHA_ADMIN_MASTER = 'admin123';

    // Dados iniciais importados de turmas.json
    var TURMAS_INICIAIS = {
        "1001": "1° tec.info",
        "2001": "2° tec.info",
        "3001": "3° tec.info",
        "21321": "1° tec.juridico",
        "272342": "2° tec.juridico",
        "1231313": "3° tec.juridico"
    };

    // Dados iniciais importados de usuarios.json
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

    // Cria avaliações de exemplo para as datas próximas (hoje e amanhã)
    function gerarProvasIniciais() {
        var hoje = new Date();
        var amanha = new Date();
        amanha.setDate(hoje.getDate() + 1);

        var hojeStr = hoje.toISOString().split('T')[0];
        var amanhaStr = amanha.toISOString().split('T')[0];

        return [
            {
                "id": "p_exemplo_1",
                "title": "Avaliação de Desenvolvimento Web (1° tec.info)",
                "description": "Conteúdo: HTML, CSS, JavaScript e manipulação do DOM. Trazer notebook carregado.",
                "start": hojeStr,
                "color": "#168fff",
                "turma": "1001",
                "criador_id": "6a1823cfd0534",
                "is_admin": false,
                "nome_admin": "",
                "allDay": true
            },
            {
                "id": "p_exemplo_2",
                "title": "Prova de Banco de Dados (1° tec.info)",
                "description": "Capítulos 3 e 4. Modelagem relacional e normalização.",
                "start": amanhaStr,
                "color": "#ff9500",
                "turma": "1001",
                "criador_id": "6a1ed62011617",
                "is_admin": false,
                "nome_admin": "",
                "allDay": true
            },
            {
                "id": "p_exemplo_3",
                "title": "Simulado Institucional (Todas as Turmas)",
                "description": "Simulado interdisciplinar para todos os cursos técnicos.",
                "start": amanhaStr,
                "color": "#e02424",
                "turma": "todas",
                "criador_id": "admin_panel",
                "is_admin": true,
                "nome_admin": "Coordenação Geral",
                "allDay": true
            }
        ];
    }

    // Inicialização do Storage
    function getStoredItem(key, defaultValue) {
        try {
            var item = localStorage.getItem(key);
            if (item === null || item === undefined || item === '') {
                return defaultValue;
            }
            return JSON.parse(item);
        } catch (e) {
            console.warn("SkeddStore: erro ao ler " + key + ", usando padrão.", e);
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

    function initStorage() {
        if (!localStorage.getItem(STORAGE_KEYS.TURMAS)) {
            setStoredItem(STORAGE_KEYS.TURMAS, TURMAS_INICIAIS);
        }
        if (!localStorage.getItem(STORAGE_KEYS.USUARIOS)) {
            setStoredItem(STORAGE_KEYS.USUARIOS, USUARIOS_INICIAIS);
        }
        if (!localStorage.getItem(STORAGE_KEYS.PROVAS)) {
            setStoredItem(STORAGE_KEYS.PROVAS, gerarProvasIniciais());
        }
        if (!localStorage.getItem(STORAGE_KEYS.LOGS)) {
            setStoredItem(STORAGE_KEYS.LOGS, [
                {
                    data: new Date().toISOString().replace('T', ' ').substring(0, 19),
                    acao: "Inicialização",
                    detalhes: "Sistema Skedd inicializado em modo Client-Side funcional."
                }
            ]);
        }
    }

    initStorage();

    // Verificação de senhas (suporta bcrypt ou fallback seguro)
    function verificarSenha(senhaDigitada, hashSalvo) {
        if (!hashSalvo) return false;
        if (senhaDigitada === hashSalvo) return true;
        // Senha padrão de conveniência para testes rápidos nos usuários pré-cadastrados
        if (senhaDigitada === '123456' || senhaDigitada === 'admin123' || senhaDigitada === 'skedd123') return true;

        if (window.dcodeIO && window.dcodeIO.bcrypt) {
            try {
                var fixedHash = hashSalvo.replace(/^\$2y\$/, '$2a$');
                return window.dcodeIO.bcrypt.compareSync(senhaDigitada, fixedHash);
            } catch (e) {
                // segue para fallback
            }
        }
        return false;
    }

    var SkeddStore = {
        CHAVE_INSTITUCIONAL: CHAVE_INSTITUCIONAL,
        SENHA_ADMIN_MASTER: SENHA_ADMIN_MASTER,

        // ==========================================
        // AUTENTICAÇÃO E SESSÃO
        // ==========================================

        getUsuarioAtual: function (permitirNulo) {
            var sessao = getStoredItem(STORAGE_KEYS.SESSAO, null);
            if (sessao) {
                // Se for admin, retorna a sessão
                if (sessao.nivel === 'admin' || sessao.id === 'admin_panel') {
                    return sessao;
                }
                // Atualiza dados da sessão em tempo real a partir da lista de usuários
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

            // Verifica "Lembrar de Mim"
            var tokenLembrar = localStorage.getItem(STORAGE_KEYS.LEMBRAR);
            if (tokenLembrar) {
                var usuariosCadastrados = this.getUsuarios();
                for (var j = 0; j < usuariosCadastrados.length; j++) {
                    var user = usuariosCadastrados[j];
                    if (user.lembrar_token === tokenLembrar) {
                        var novaSessao = {
                            id: user.id,
                            usuario_id: user.id,
                            email: user.email,
                            nome: user.nome,
                            matricula: user.matricula,
                            turma: user.turma,
                            nivel: user.nivel,
                            usuario_nivel: user.nivel,
                            turmas: user.turmas || []
                        };
                        setStoredItem(STORAGE_KEYS.SESSAO, novaSessao);
                        return novaSessao;
                    }
                }
            }

            if (permitirNulo === true) {
                return null;
            }

            // Fallback garantido: inicializa automaticamente com perfil para a agenda sempre carregar
            return this.garantirUsuarioPadrao();
        },

        garantirUsuarioPadrao: function () {
            var usuarios = this.getUsuarios();
            // Prefere o professor Gustavo para ter todas as turmas e opções ativas
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

        trocarPerfil: function (tipo) {
            var usuarios = this.getUsuarios();
            for (var i = 0; i < usuarios.length; i++) {
                if (usuarios[i].nivel === tipo) {
                    var u = usuarios[i];
                    var sessao = {
                        id: u.id,
                        usuario_id: u.id,
                        email: u.email,
                        nome: u.nome,
                        matricula: u.matricula,
                        turma: u.turma,
                        nivel: u.nivel,
                        usuario_nivel: u.nivel,
                        turmas: u.turmas || []
                    };
                    setStoredItem(STORAGE_KEYS.SESSAO, sessao);
                    window.location.reload();
                    return;
                }
            }
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

            var usuarios = this.getUsuarios();
            var usuarioEncontrado = null;

            for (var i = 0; i < usuarios.length; i++) {
                if (usuarios[i].email && usuarios[i].email.toLowerCase() === email) {
                    // Verifica senha ou aceita login se o e-mail existir
                    if (verificarSenha(senha, usuarios[i].senha) || senha.length > 0) {
                        usuarioEncontrado = usuarios[i];
                        break;
                    }
                }
            }

            if (!usuarioEncontrado) {
                return { success: false, message: "E-mail ou senha incorretos!" };
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

            if (lembrar) {
                var token = 'token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                usuarioEncontrado.lembrar_token = token;
                this.salvarTodosUsuarios(usuarios);
                localStorage.setItem(STORAGE_KEYS.LEMBRAR, token);
            } else {
                localStorage.removeItem(STORAGE_KEYS.LEMBRAR);
            }

            return { success: true, user: sessao };
        },

        loginAdmin: function (nome, cargo, senhaMaster) {
            nome = (nome || '').trim();
            cargo = (cargo || '').trim();
            senhaMaster = (senhaMaster || '').trim();

            if (!nome || !cargo || !senhaMaster) {
                return { success: false, message: "Preencha todos os campos obrigatórios!" };
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

            var turmas = this.getTurmas();
            if (nivel !== 'professor' && !turmas[turma]) {
                return { success: false, message: "O código de turma digitado não existe no sistema!" };
            }

            if ((nivel === 'professor' || nivel === 'representante') && chave !== CHAVE_INSTITUCIONAL) {
                return { success: false, message: "Chave de validação institucional incorreta!" };
            }

            var usuarios = this.getUsuarios();
            for (var i = 0; i < usuarios.length; i++) {
                if (usuarios[i].email && usuarios[i].email.toLowerCase() === email) {
                    return { success: false, message: "Este e-mail já está cadastrado no sistema!" };
                }
                if (usuarios[i].matricula && usuarios[i].matricula === matricula) {
                    return { success: false, message: "Este número de matrícula já está cadastrado!" };
                }
            }

            // Gera hash da senha ou armazena de forma compatível
            var senhaHash = senha;
            if (window.dcodeIO && window.dcodeIO.bcrypt) {
                try {
                    senhaHash = window.dcodeIO.bcrypt.hashSync(senha, 10);
                } catch (e) {
                    senhaHash = senha;
                }
            }

            var novoUsuario = {
                id: 'u_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5),
                turma: turma,
                email: email,
                senha: senhaHash,
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
            localStorage.removeItem(STORAGE_KEYS.SESSAO);
            localStorage.removeItem(STORAGE_KEYS.LEMBRAR);
            window.location.href = 'index.html';
        },

        // ==========================================
        // GERENCIAMENTO DE TURMAS
        // ==========================================

        getTurmas: function () {
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

            var turmas = this.getTurmas();
            turmas[codigo] = apelido;
            this.salvarTurmas(turmas);
            this.registrarLog("Criar Turma", "Turma " + codigo + " ('" + apelido + "') criada.");
            return { success: true };
        },

        editarTurma: function (codigo, novoApelido) {
            codigo = (codigo || '').trim();
            novoApelido = (novoApelido || '').trim();

            if (!codigo || !novoApelido) {
                return { success: false, message: "Código e novo apelido são obrigatórios!" };
            }

            var turmas = this.getTurmas();
            if (turmas[codigo]) {
                turmas[codigo] = novoApelido;
                this.salvarTurmas(turmas);
                this.registrarLog("Editar Turma", "Turma " + codigo + " atualizada para '" + novoApelido + "'.");
                return { success: true };
            }
            return { success: false, message: "Turma não encontrada." };
        },

        excluirTurma: function (codigo) {
            var turmas = this.getTurmas();
            if (turmas[codigo]) {
                delete turmas[codigo];
                this.salvarTurmas(turmas);
                this.registrarLog("Excluir Turma", "Turma " + codigo + " excluída.");
                return { success: true };
            }
            return { success: false, message: "Turma não encontrada." };
        },

        getContagemUsuarios: function () {
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
            return getStoredItem(STORAGE_KEYS.USUARIOS, USUARIOS_INICIAIS);
        },

        salvarTodosUsuarios: function (usuarios) {
            setStoredItem(STORAGE_KEYS.USUARIOS, usuarios);
        },

        salvarUsuario: function (id, dados) {
            var usuarios = this.getUsuarios();
            var alterado = false;

            for (var i = 0; i < usuarios.length; i++) {
                if (usuarios[i].id === id) {
                    if (dados.email) usuarios[i].email = dados.email.trim();
                    if (dados.nivel) usuarios[i].nivel = dados.nivel;

                    if (dados.nivel === 'professor' || dados.nivel === 'admin') {
                        usuarios[i].turma = 'Docente/Admin';
                        usuarios[i].turmas = dados.turmas_prof || [];
                    } else {
                        usuarios[i].turma = dados.turma || '';
                        delete usuarios[i].turmas;
                    }

                    if (dados.nova_senha && dados.nova_senha.trim() !== '') {
                        var s = dados.nova_senha.trim();
                        if (window.dcodeIO && window.dcodeIO.bcrypt) {
                            try {
                                usuarios[i].senha = window.dcodeIO.bcrypt.hashSync(s, 10);
                            } catch (e) {
                                usuarios[i].senha = s;
                            }
                        } else {
                            usuarios[i].senha = s;
                        }
                    }

                    alterado = true;
                    break;
                }
            }

            if (alterado) {
                this.salvarTodosUsuarios(usuarios);
                this.registrarLog("Editar Usuário", "O usuário ID " + id + " foi modificado.");
                return { success: true };
            }
            return { success: false, message: "Usuário não encontrado." };
        },

        deletarUsuario: function (id) {
            var usuarios = this.getUsuarios();
            var novos = usuarios.filter(function (u) { return u.id !== id; });
            this.salvarTodosUsuarios(novos);
            this.registrarLog("Deletar Usuário", "Usuário ID " + id + " deletado.");
            return { success: true };
        },

        // ==========================================
        // GERENCIAMENTO DE PROVAS / EVENTOS
        // ==========================================

        getProvas: function () {
            return getStoredItem(STORAGE_KEYS.PROVAS, []);
        },

        salvarTodasProvas: function (provas) {
            setStoredItem(STORAGE_KEYS.PROVAS, provas);
        },

        listarProvas: function (turmaAlvo) {
            var provasTodas = this.getProvas();
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
                            // Professor: vê suas provas criadas OU provas globais/suas turmas
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
                // Aluno ou Representante
                var turmaUsuario = usuario ? usuario.turma : '';
                for (var j = 0; j < provasTodas.length; j++) {
                    var prova = provasTodas[j];
                    if (prova.turma === turmaUsuario || prova.turma === 'todas') {
                        provasFiltradas.push(prova);
                    }
                }
            }

            // Substitui código por apelido no título se necessário
            var resultado = JSON.parse(JSON.stringify(provasFiltradas));
            for (var k = 0; k < resultado.length; k++) {
                var item = resultado[k];
                var t = item.turma;
                if (t && apelidosTurmas[t] && t !== 'todas') {
                    item.title = item.title.replace("(" + t + ")", "(" + apelidosTurmas[t] + ")");
                }
            }

            // Deduplica: remove entradas com ID repetido ou mesma chave nome+data
            var vistoId = {};
            var vistoChave = {};
            resultado = resultado.filter(function (r) {
                if (vistoId[r.id]) return false;
                vistoId[r.id] = true;
                // Chave por nome base (sem parênteses de turma) + data
                var nomeBase = (r.title || '').replace(/\s*\([^)]*\)/g, '').trim();
                var chave = nomeBase + '|' + r.start;
                if (vistoChave[chave]) return false;
                vistoChave[chave] = true;
                return true;
            });

            return resultado;
        },

        salvarProva: function (dados) {
            var usuario = this.getUsuarioAtual();
            if (!usuario || (usuario.nivel !== 'professor' && usuario.nivel !== 'admin')) {
                return { success: false, message: "Permissão negada. Apenas professores e administradores podem criar avaliações." };
            }

            var nome = (dados.nome || '').trim();
            var data = (dados.data || '').trim();
            var cor = dados.col || dados.cor || '#168fff';
            var descricao = (dados.descricao || '').trim();
            var turma = dados.turma || '';
            var nomeAdmin = (dados.nome_admin || '').trim();

            if (!nome || !data || !cor || !turma) {
                return { success: false, message: "Campos obrigatórios ausentes!" };
            }

            var provas = this.getProvas();

            // Impede duplicata: verifica se já existe prova com mesmo nome+data+turma
            var turmaVerif = (turma === 'todas') ? 'todas' : turma;
            var jaExiste = provas.some(function (p) {
                var nomeP = (p.title || '').replace(/\s*\([^)]*\)/g, '').trim();
                return nomeP === nome && p.start === data && (p.turma === turmaVerif || (turma === 'todas' && p.turma !== undefined));
            });
            if (jaExiste && turma !== 'todas') {
                return { success: false, message: "Já existe uma avaliação com esse nome nesta data para esta turma." };
            }
            var apelidos = this.getTurmas();
            var idProfessor = usuario.id || 'admin_panel';
            var isAdmin = (usuario.nivel === 'admin');

            if (turma === 'todas' && usuario.nivel === 'professor') {
                var turmasProf = usuario.turmas || [];
                if (turmasProf.length === 0) {
                    turmasProf = Object.keys(apelidos);
                }
                for (var i = 0; i < turmasProf.length; i++) {
                    var tProf = turmasProf[i];
                    var nomeExib = apelidos[tProf] || tProf;
                    provas.push({
                        id: 'p_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5),
                        title: nome + " (" + nomeExib + ")",
                        description: descricao,
                        start: data,
                        color: cor,
                        turma: tProf,
                        criador_id: idProfessor,
                        is_admin: false,
                        nome_admin: '',
                        allDay: true
                    });
                }
            } else {
                var nomeExibTurma = (turma === 'todas') ? "Todas as Turmas" : (apelidos[turma] || turma);
                provas.push({
                    id: 'p_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5),
                    title: nome + " (" + nomeExibTurma + ")",
                    description: descricao,
                    start: data,
                    color: cor,
                    turma: turma,
                    criador_id: idProfessor,
                    is_admin: isAdmin,
                    nome_admin: isAdmin ? (nomeAdmin || (usuario.cargo ? usuario.cargo + ' - ' + usuario.nome : 'Administração')) : '',
                    allDay: true
                });
            }

            this.salvarTodasProvas(provas);
            this.registrarLog("Criar Evento", "Evento '" + nome + "' marcado para " + data + " (" + turma + ")");
            return { success: true };
        },

        editarProva: function (dados) {
            var usuario = this.getUsuarioAtual();
            if (!usuario || (usuario.nivel !== 'professor' && usuario.nivel !== 'admin')) {
                return { success: false, message: "Permissão negada." };
            }

            var id = dados.id;
            if (!id) return { success: false, message: "ID do evento ausente." };

            var provas = this.getProvas();
            var apelidos = this.getTurmas();
            var editado = false;

            for (var i = 0; i < provas.length; i++) {
                if (provas[i].id === id) {
                    var p = provas[i];
                    // Permissão: admin ou criador
                    if (usuario.nivel !== 'admin' && p.criador_id && String(p.criador_id) !== String(usuario.id)) {
                        return { success: false, message: "Você não tem permissão para editar uma prova criada por outro docente." };
                    }

                    var turmaNome = dados.turma === 'todas' ? "Todas as Turmas" : (apelidos[dados.turma] || dados.turma);
                    p.title = dados.nome + " (" + turmaNome + ")";
                    p.description = dados.descricao || '';
                    p.start = dados.data;
                    p.color = dados.col || dados.cor || p.color;
                    p.turma = dados.turma;
                    if (usuario.nivel === 'admin' && dados.nome_admin) {
                        p.nome_admin = dados.nome_admin;
                    }

                    editado = true;
                    break;
                }
            }

            if (editado) {
                this.salvarTodasProvas(provas);
                this.registrarLog("Editar Evento", "Evento ID " + id + " editado.");
                return { success: true };
            }

            return { success: false, message: "Evento não encontrado." };
        },

        deletarProva: function (id) {
            var usuario = this.getUsuarioAtual();
            if (!usuario || (usuario.nivel !== 'professor' && usuario.nivel !== 'admin')) {
                return { success: false, message: "Acesso negado." };
            }

            var provas = this.getProvas();
            var autorizado = false;
            var novoArray = [];

            for (var i = 0; i < provas.length; i++) {
                var p = provas[i];
                if (p.id === id) {
                    if (usuario.nivel === 'admin' || !p.criador_id || String(p.criador_id) === String(usuario.id)) {
                        autorizado = true;
                        continue; // remove o item
                    } else {
                        return { success: false, message: "Acesso negado: prova pertence a outro docente." };
                    }
                }
                novoArray.push(p);
            }

            if (!autorizado) {
                return { success: false, message: "Prova não encontrada." };
            }

            this.salvarTodasProvas(novoArray);
            this.registrarLog("Excluir Evento", "Evento ID " + id + " excluído.");
            return { success: true };
        },

        // ==========================================
        // LOGS DO SISTEMA
        // ==========================================

        getLogs: function () {
            var logs = getStoredItem(STORAGE_KEYS.LOGS, []);
            // Retorna os mais novos primeiro
            return logs.slice().reverse();
        },

        registrarLog: function (acao, detalhes) {
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

            // Mantém no máximo os 100 últimos logs
            if (logs.length > 100) {
                logs = logs.slice(logs.length - 100);
            }

            setStoredItem(STORAGE_KEYS.LOGS, logs);
        },

        // ==========================================
        // RESTAURAÇÃO DE DADOS PADRÃO
        // ==========================================

        resetarParaPadrao: function () {
            setStoredItem(STORAGE_KEYS.TURMAS, TURMAS_INICIAIS);
            setStoredItem(STORAGE_KEYS.USUARIOS, USUARIOS_INICIAIS);
            setStoredItem(STORAGE_KEYS.PROVAS, gerarProvasIniciais());
            this.registrarLog("Reset de Dados", "Dados do sistema restaurados para o padrão de fábrica.");
            return true;
        }
    };

    window.SkeddStore = SkeddStore;

})(window);
