$(document).ready(function () {
    console.log("Skedd: DOM carregado e pronto para iniciar o FullCalendar.");

    // --- 0. CONFIGURAÇÃO DINÂMICA DA INTERFACE DO USUÁRIO ---
    function configurarInterfaceUsuario() {
        if (!window.SkeddStore) return;
        var usuario = SkeddStore.getUsuarioAtual();
        if (!usuario) {
            usuario = SkeddStore.garantirUsuarioPadrao();
        }

        window.usuarioLogadoId = usuario.id;
        window.usuarioNivel = usuario.nivel;
        window.usuarioAtualObj = usuario;

        var apelidos = SkeddStore.getTurmas();

        // 1. Cabeçalho
        $('#nomeUsuarioHeader').text(usuario.nome || 'Usuário');
        var mat = usuario.matricula ? 'Matrícula: ' + usuario.matricula : (usuario.nivel === 'admin' ? 'Administrador' : 'Sem Matrícula');
        $('#detalheUsuarioHeader').text(mat);

        // 2. Painel Lateral
        if (usuario.nivel === 'professor' || usuario.nivel === 'admin') {
            $('#sidebarProfessor').show();
            $('#sidebarAluno').hide();

            if (usuario.nivel === 'admin') {
                $('#boxBannerAdmin').show();
                $('#boxInputAdminNome').show();
                var cargo = usuario.cargo || usuario.admin_cargo || 'Administração';
                var nomeAdmin = usuario.nome || 'Direção';
                $('#nomeAdmin').val(cargo + ' - ' + nomeAdmin);
            }

            // Popula opções de turmas
            var selectTurma = $('#turmaProva');
            selectTurma.empty();
            selectTurma.append('<option value="todas">Todas as suas turmas</option>');

            var turmasDisponiveis = [];
            if (usuario.nivel === 'admin') {
                turmasDisponiveis = Object.keys(apelidos);
            } else {
                turmasDisponiveis = usuario.turmas || [];
                if (turmasDisponiveis.length === 0) {
                    turmasDisponiveis = Object.keys(apelidos);
                }
            }

            if (turmasDisponiveis.length > 0) {
                turmasDisponiveis.forEach(function (cod) {
                    var nomeExib = apelidos[cod] ? cod + ' - ' + apelidos[cod] : 'Turma ' + cod;
                    selectTurma.append('<option value="' + cod + '">' + nomeExib + '</option>');
                });
            } else {
                selectTurma.append('<option value="" disabled>Nenhuma turma vinculada</option>');
            }
        } else {
            // Modo Aluno
            $('#sidebarProfessor').hide();
            $('#sidebarAluno').show();
            var turmaUser = usuario.turma || '1001';
            var nomeTurma = apelidos[turmaUser] ? turmaUser + ' (' + apelidos[turmaUser] + ')' : (turmaUser || '1001');
            $('#nomeTurmaExibicaoAluno').text(nomeTurma);
        }
    }

    configurarInterfaceUsuario();

    // --- 1. LÓGICA DO MENU DE CONFIGURAÇÕES E NOTIFICAÇÕES ---
    $('#btnConfig').click(function (e) {
        e.stopPropagation();
        $('#notifMenu').hide();
        $('#configMenu').toggle();
    });

    $('#btnNotif').click(function (e) {
        e.stopPropagation();
        $('#configMenu').hide();
        $('#notifMenu').toggle();
    });

    $(document).click(function () {
        $('#configMenu').hide();
        $('#notifMenu').hide();
    });

    $('#configMenu, #notifMenu').click(function (e) {
        e.stopPropagation();
    });

    // --- 2. LÓGICA DO TEMA (DARK MODE) ---
    $('#toggleTheme').click(function () {
        $('body').toggleClass('dark-mode');
        if ($('body').hasClass('dark-mode')) {
            localStorage.setItem('tema', 'dark');
        } else {
            localStorage.setItem('tema', 'light');
        }
    });

    if (localStorage.getItem('tema') === 'dark') {
        $('body').addClass('dark-mode');
    }

    // --- 3. LÓGICA DO CALENDÁRIO E DEMAIS FUNÇÕES ---
    var dataSelecionadaGlobal = moment().format('YYYY-MM-DD');

    // Limpa provas duplicadas que possam existir no localStorage
    (function limparDuplicatas() {
        if (!window.SkeddStore) return;
        var provas = SkeddStore.getProvas();
        var vistoChave = {};
        var limpas = provas.filter(function (p) {
            // Chave única: nome base (sem parênteses) + data + turma
            var nomeBase = (p.title || '').replace(/\s*\([^)]*\)/g, '').trim();
            var chave = nomeBase + '|' + p.start + '|' + (p.turma || '');
            if (vistoChave[chave]) return false;
            vistoChave[chave] = true;
            return true;
        });
        if (limpas.length < provas.length) {
            console.log('[Skedd] Removidas ' + (provas.length - limpas.length) + ' provas duplicadas do armazenamento.');
            SkeddStore.salvarTodasProvas(limpas);
        }
    })();

    // Configura o locale do moment antes de iniciar o FullCalendar
        // ---------------------------------------------------------------
    // RENDERIZAÇÃO DOS PONTOS DO CALENDÁRIO
    // Padrão "última chamada vence": usa um contador crescente.
    // Cada agendamento captura o valor atual; quando o setTimeout
    // dispara, verifica se o ID ainda é o mais recente. Se não for,
    // descarta — assim nunca roda mais de uma vez por ciclo.
    // ---------------------------------------------------------------
    var _pontosCicloId = 0;

    function scheduleRenderPontos(provas) {
        var meuId = ++_pontosCicloId;
        var snapshot = provas || window._skeddProvasAtuais || [];
        setTimeout(function () {
            if (meuId !== _pontosCicloId) return; // chamada mais nova já foi agendada

            // Remove pontos antigos
            $('.dice-container').remove();

            // Agrupa por data (deduplicando por ID de prova)
            var eventosPorDia = {};
            var idsVistos = {};
            snapshot.forEach(function (p) {
                if (idsVistos[p.id]) return;
                idsVistos[p.id] = true;
                var dataStr = String(p.start).substring(0, 10);
                if (!eventosPorDia[dataStr]) eventosPorDia[dataStr] = [];
                eventosPorDia[dataStr].push(p);
            });

            // Insere os pontinhos nos dias do calendário
            for (var dataStr in eventosPorDia) {
                var cell = $('.fc-day-top[data-date="' + dataStr + '"]');
                if (cell.length) {
                    var lista = eventosPorDia[dataStr];
                    var num = Math.min(lista.length, 9);
                    var container = $('<div class="dice-container dice-' + num + '"></div>');
                    lista.forEach(function (p, idx) {
                        if (idx < 9) {
                            var dot = $('<span class="dice-dot"></span>');
                            dot.css('background-color', p.color || 'var(--header)');
                            container.append(dot);
                        }
                    });
                    cell.append(container);
                }
            }

            if (dataSelecionadaGlobal) atualizarPainelDireito(dataSelecionadaGlobal);
        }, 0);
    }

    $('#calendar').fullCalendar({
        header: { left: 'prev,next', center: 'title', right: '' },
        // columnHeader: false é inválido no FullCalendar 3 — ocultamos via CSS
        editable: false,

        events: function (start, end, timezone, callback) {
            var usuario = SkeddStore ? SkeddStore.getUsuarioAtual() : null;
            var isProfessor = (window.usuarioNivel === 'professor' || window.usuarioNivel === 'admin');
            var turmaDefinida = isProfessor ? ($('#turmaProva').val() || 'todas') : (usuario ? usuario.turma : 'todas');

            var provas = SkeddStore ? SkeddStore.listarProvas(turmaDefinida) : [];

            // Salva as provas atuais SINCRONAMENTE (antes do setTimeout)
            window._skeddProvasAtuais = provas;

            setTimeout(function () {
                callback(provas);
                verificarProvasNotificacoes(provas);

                if ($('#listaGerenciarProvas').length && isProfessor) {
                    renderizarListaGerenciamentoProfessor(provas);
                }
            }, 0);
        },

        locale: 'pt-br',

        eventRender: function (event, element) {
            // O CSS oculta o conteúdo nativo do FC; os pontos são desenhados via scheduleRenderPontos.
        },

        eventAfterAllRender: function (view) {
            // Cada vez que o FC termina de renderizar, agenda os pontos.
            // O padrão "última chamada vence" garante que só a última execução rode.
            scheduleRenderPontos(window._skeddProvasAtuais || []);
        },

        dayClick: function (date, jsEvent, view) {
            var isProfessor = (window.usuarioNivel === 'professor' || window.usuarioNivel === 'admin');
            if (isProfessor && ($('#turmaProva').val() === '')) {
                return;
            }

            var dataClicada = moment(date).format('YYYY-MM-DD');
            dataSelecionadaGlobal = dataClicada;
            $('#dataProva').val(dataClicada); // Sincroniza com o formulário
            var eventosDoDia = $('#calendar').fullCalendar('clientEvents', function (event) {
                var d = (event.start && event.start.format) ? event.start.format('YYYY-MM-DD') : moment(event.start).format('YYYY-MM-DD');
                return d === dataClicada;
            });

            if (eventosDoDia.length > 0) {
                exibirDescricaoNaLateral(eventosDoDia[0]);
            } else {
                $('#painelDescricaoAluno').fadeOut();
                $('#painelDescricao').fadeOut();
            }
            atualizarPainelDireito(dataClicada);
        },

        eventClick: function (event) {
            exibirDescricaoNaLateral(event);
        }
    });

    function exibirDescricaoNaLateral(event) {
        var corSelecionada = event.color ? event.color : 'var(--header)';

        if ($('#painelDescricao').length) {
            var tituloAdmin = event.is_admin ? '⭐ ' + event.title : event.title;
            $('#tituloDescricaoProva').text(tituloAdmin).css('color', corSelecionada);
            var autoriaAdmin = event.is_admin ? '<strong>Publicado por: ' + (event.nome_admin || 'Administração') + '</strong><br>' : '';
            var descricao = event.description ? event.description : "Nenhuma descrição fornecida.";
            $('#textoDescricaoProva').html(autoriaAdmin + descricao);
            $('#painelDescricao').data('id', event.id).fadeIn();

            // Compara autoria
            if (!event.criador_id || String(event.criador_id) === String(window.usuarioLogadoId) || window.usuarioNivel === 'admin') {
                $('#btnDesmarcar').show();
                $('#btnEditar').show();
                $('#painelDescricao').data('provaCompleta', event);
            } else {
                $('#btnDesmarcar').hide();
                $('#btnEditar').hide();
            }
        }

        if ($('#painelDescricaoAluno').length) {
            var tituloAdminA = event.is_admin ? '⭐ ' + event.title : event.title;
            $('#tituloDescricaoAluno').text(tituloAdminA).css('color', corSelecionada);
            var autoriaAdminA = event.is_admin ? '<strong>Publicado por: ' + (event.nome_admin || 'Administração') + '</strong><br>' : '';
            var descricaoA = event.description ? event.description : "Nenhuma descrição fornecida.";
            $('#textoDescricaoAluno').html(autoriaAdminA + descricaoA);
            $('#painelDescricaoAluno').fadeIn();
        }
    }

    $(document).on('click', '#btnDesmarcar', function () {
        var idProva = $('#painelDescricao').data('id');
        if (idProva && confirm("Deseja realmente desmarcar esta prova?")) {
            var res = SkeddStore.deletarProva(idProva);
            if (res.success) {
                $('#calendar').fullCalendar('refetchEvents');
                $('#painelDescricao').fadeOut();
                alert("Prova desmarcada com sucesso!");
            } else {
                alert("Erro: " + res.message);
            }
        }
    });

    $(document).on('click', '#btnEditar', function () {
        var prova = $('#painelDescricao').data('provaCompleta');
        if (prova) {
            var nomeLimpo = prova.title.replace(/\s*\([^)]*\)/g, '');
            $('#nomeProva').val(nomeLimpo);
            $('#descricaoProva').val(prova.description || '');
            var dFormat = (prova.start && prova.start.format) ? prova.start.format('YYYY-MM-DD') : moment(prova.start).format('YYYY-MM-DD');
            $('#dataProva').val(dFormat);
            $('#corProva').val(prova.color || '#168fff');
            $('#turmaProva').val(prova.turma || '');

            $('#btnMarcar').text('Salvar Alterações')
                .data('modo', 'editar')
                .data('idProva', prova.id);

            $('.sidebar-left').fadeTo(100, 0.5).fadeTo(100, 1);
        }
    });

    $('#turmaProva').on('change', function () {
        $('#painelDescricao').fadeOut();
        $('#painelDescricaoAluno').fadeOut();
        if ($(this).val() === '') {
            dataSelecionadaGlobal = '';
            $('#listaProvas').empty().append('<li class="vazio">Selecione uma turma para ver as avaliações.</li>');
        }
        $('#calendar').fullCalendar('refetchEvents');
    });

    $('#btnMarcar').click(function () {
        var nome = $('#nomeProva').val();
        var descricao = $('#descricaoProva').val();
        var data = $('#dataProva').val();
        var col = $('#corProva').val();
        var turma = $('#turmaProva').val() || '';

        var modo = $(this).data('modo') || 'salvar';
        var idEditado = $(this).data('idProva');

        if (turma === '') {
            alert("Por favor, selecione uma turma específica antes de marcar a prova.");
            return;
        }

        if (nome !== '' && data !== '') {
            var nomeAdmin = $('#nomeAdmin').length ? $('#nomeAdmin').val() : '';
            var dados = {
                nome: nome,
                descricao: descricao,
                data: data,
                col: col,
                turma: turma,
                nome_admin: nomeAdmin
            };

            var res;
            if (modo === 'editar') {
                dados.id = idEditado;
                res = SkeddStore.editarProva(dados);
            } else {
                res = SkeddStore.salvarProva(dados);
            }

            if (res.success) {
                $('#calendar').fullCalendar('refetchEvents');
                alert(modo === 'editar' ? "Prova atualizada com sucesso!" : "Prova marcada com sucesso!");

                $('#nomeProva').val('');
                $('#descricaoProva').val('');
                $('#dataProva').val('');
                $('#corProva').val('#168fff');
                $('#btnMarcar').text('Salvar Evento').data('modo', 'salvar').removeData('idProva');
                $('#painelDescricao').fadeOut();
            } else {
                alert("Erro: " + res.message);
            }
        } else {
            alert("Preencha o nome e a data da prova.");
        }
    });

    function atualizarPainelDireito(dataStr) {
        var dataFormatada = moment(dataStr).format('DD/MM/YYYY');
        $('#dataSelecionada').text(dataFormatada);
        var todosEventos = $('#calendar').fullCalendar('clientEvents');
        var listaHtml = $('#listaProvas');
        listaHtml.empty();
        var encontrouProva = false;

        todosEventos.forEach(function (evento) {
            var dataEvento = (evento.start && evento.start.format) ? evento.start.format('YYYY-MM-DD') : moment(evento.start).format('YYYY-MM-DD');
            if (dataEvento === dataStr) {
                var tituloAdmin = evento.is_admin ? '⭐ ' + evento.title : evento.title;
                var conteudoHTML = '<strong>' + tituloAdmin + '</strong>';
                if (evento.description) {
                    var autoriaAdmin = evento.is_admin ? '<span style="color:#168fff; font-size: 11px;">[' + (evento.nome_admin || 'Administração') + ']</span> ' : '';
                    conteudoHTML += '<p class="desc-preview">' + autoriaAdmin + evento.description + '</p>';
                }
                var li = $('<li class="quadrado-prova"></li>').html(conteudoHTML);
                li.css('border-top', '8px solid ' + (evento.color || 'var(--header)'));

                li.on('click', function () {
                    exibirDescricaoNaLateral(evento);
                });

                listaHtml.append(li);
                encontrouProva = true;
            }
        });

        if (!encontrouProva) {
            listaHtml.append('<li class="vazio">Nenhuma avaliação nesta data.</li>');
        }
    }

    function renderizarListaGerenciamentoProfessor(provas) {
        var container = $('#listaGerenciarProvas');
        container.empty();

        if ($('#turmaProva').val() === '') {
            container.append('<p style="font-style: italic; color: #888; font-size: 13px; text-align: center; margin-top: 10px;">Selecione uma turma para gerenciar.</p>');
            return;
        }

        if (!provas || provas.length === 0) {
            container.append('<p style="font-style: italic; color: #888; font-size: 13px; text-align: center; margin-top: 10px;">Nenhuma prova agendada.</p>');
            return;
        }

        provas.sort(function (a, b) {
            return moment(a.start).diff(moment(b.start));
        });

        provas.forEach(function (prova) {
            var dataFormatada = moment(prova.start).format('DD/MM/YYYY');
            var ehDono = (!prova.criador_id || String(prova.criador_id) === String(window.usuarioLogadoId) || window.usuarioNivel === 'admin');

            var botaoDeletar = ehDono
                ? `<button class="btn-deletar-direto" data-id="${prova.id}" title="Desmarcar Prova" style="background: #ff3b30; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px; line-height: 1;">&times;</button>`
                : `<span style="font-size: 11px; color: #888; font-style: italic; background: rgba(255,255,255,0.08); padding: 2px 6px; border-radius: 4px;">Outro Prof.</span>`;

            var tituloAdmin = prova.is_admin ? '⭐ ' + prova.title : prova.title;
            var itemHtml = `
                <div class="item-gerenciar-prova" style="background: rgba(0, 0, 0, 0.05); padding: 10px; border-radius: 8px; margin-bottom: 8px; border-left: 5px solid ${prova.color}; display: flex; justify-content: space-between; align-items: center; gap: 10px; opacity: ${ehDono ? '1' : '0.8'};">
                    <div style="flex: 1; min-width: 0;">
                        <strong style="font-size: 13px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: ${prova.color};">${tituloAdmin}</strong>
                        <span style="font-size: 11px; color: #888;">📅 ${dataFormatada}</span>
                    </div>
                    ${botaoDeletar}
                </div>
            `;
            container.append(itemHtml);
        });
    }

    $(document).on('click', '.btn-deletar-direto', function (e) {
        e.preventDefault();
        var idProva = $(this).data('id');

        if (idProva && confirm("Deseja realmente desmarcar esta prova?")) {
            var res = SkeddStore.deletarProva(idProva);
            if (res.success) {
                $('#calendar').fullCalendar('refetchEvents');
                $('#painelDescricao').fadeOut();
                alert("Prova desmarcada com sucesso!");
            } else {
                alert("Erro: " + res.message);
            }
        }
    });

    // --- 4. SISTEMA DE NOTIFICAÇÃO E POPUPS ---
    $('body').append('<div id="toast-container" class="toast-container"></div>');
    var notificados = {};

    function showToast(title, body, type) {
        var cardClass = (type === 'today') ? 'alert-today' : 'alert-tomorrow';
        var toastId = 'toast-' + Date.now() + Math.floor(Math.random() * 1000);
        var toastHtml = `
            <div class="toast-card ${cardClass}" id="${toastId}">
                <img src="https://i.ibb.co/ymJC5sNN/Captura-de-tela-2026-05-19-100134-1.webp" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;" alt="Logo">
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-body">${body}</div>
                </div>
                <button class="toast-close" onclick="$('#${toastId}').fadeOut(300, function(){ $(this).remove(); })">&times;</button>
            </div>
        `;
        $('#toast-container').append(toastHtml);

        setTimeout(function () {
            var toastEl = $('#' + toastId);
            if (toastEl.length) {
                toastEl.css('animation', 'fadeOutToast 0.5s ease forwards');
                setTimeout(function () {
                    toastEl.remove();
                }, 500);
            }
        }, 7000);
    }

    function verificarProvasNotificacoes(provas) {
        if (!Array.isArray(provas)) return;
        var hojeStr = moment().format('YYYY-MM-DD');
        var amanhaStr = moment().add(1, 'days').format('YYYY-MM-DD');

        var provasHoje = [];
        var provasAmanha = [];

        provas.forEach(function (prova) {
            var dataProva = moment(prova.start).format('YYYY-MM-DD');
            if (dataProva === hojeStr) {
                provasHoje.push(prova);
            } else if (dataProva === amanhaStr) {
                provasAmanha.push(prova);
            }
        });

        var listHtml = $('#listaNotificacoes');
        listHtml.empty();

        var totalNotifs = provasHoje.length + provasAmanha.length;

        if (totalNotifs > 0) {
            $('#notif-badge').text(totalNotifs).show();

            provasHoje.forEach(function (prova) {
                var tituloLimpo = prova.title.replace(/\s*\([^)]*\)/g, '');
                listHtml.append(`
                    <li class="notif-item" style="border-left: 4px solid #ff3b30; margin-bottom: 5px; background: rgba(255, 59, 48, 0.05); padding: 8px; border-radius: 4px;">
                        <strong>Hoje:</strong> ${tituloLimpo}
                        <div style="font-size: 11px; color: var(--secondary); margin-top: 2px;">
                            ${prova.description ? prova.description : 'Sem observações.'}
                        </div>
                    </li>
                `);

                if (!notificados[prova.id]) {
                    showToast(tituloLimpo, '⚠️ Prova marcada para HOJE!', 'today');
                    notificados[prova.id] = true;
                }
            });

            provasAmanha.forEach(function (prova) {
                var tituloLimpo = prova.title.replace(/\s*\([^)]*\)/g, '');
                listHtml.append(`
                    <li class="notif-item" style="border-left: 4px solid #ffcc00; margin-bottom: 5px; background: rgba(255, 204, 0, 0.05); padding: 8px; border-radius: 4px;">
                        <strong>Amanhã:</strong> ${tituloLimpo}
                        <div style="font-size: 11px; color: var(--secondary); margin-top: 2px;">
                            ${prova.description ? prova.description : 'Sem observações.'}
                        </div>
                    </li>
                `);

                if (!notificados[prova.id]) {
                    showToast(tituloLimpo, '📅 Prova marcada para amanhã!', 'tomorrow');
                    notificados[prova.id] = true;
                }
            });
        } else {
            $('#notif-badge').hide();
            listHtml.append(`
                <li style="color: var(--secondary); font-style: italic; padding: 10px; text-align: center;">
                    Nenhuma avaliação hoje ou amanhã.
                </li>
            `);
        }
    }

    // --- 5. PERMISSÃO PARA NOTIFICAÇÕES NATIVAS DO NAVEGADOR ---
    if ('Notification' in window && Notification.permission === 'default') {
        setTimeout(function () {
            Notification.requestPermission();
        }, 3000);
    }
});