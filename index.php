<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Se já estiver logado via sessão PHP, redireciona diretamente para agenda.php
if (!empty($_SESSION['skedd_usuario'])) {
    header('Location: agenda.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skedd - Entrar ou Cadastrar</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="https://i.ibb.co/ymJC5sNN/Captura-de-tela-2026-05-19-100134-1.webp" id="icone-redondo">

    <style>
        * { box-sizing: border-box; }

        .container-login {
            background: white;
            color: black;
            padding: 28px 24px;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
            position: relative;
        }

        .container-login h2 {
            margin: 8px 0 0;
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 12px 14px;
            border: 1px solid #e0e5ea;
            border-radius: 12px;
            font-size: 16px; 
            outline: none;
            transition: box-shadow 0.2s, border-color 0.2s;
            background: #fff;
            color: #333;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #168fff;
            box-shadow: 0 0 0 3px rgba(22, 143, 255, 0.2);
        }

        button[type="submit"], .btn-principal {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #168fff, #0077ff);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 4px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08);
        }

        button[type="submit"]:hover,
        button[type="submit"]:active {
            background: linear-gradient(135deg, #1276d1, #0056b3);
            transform: translateY(-2px);
            box-shadow: 0 14px 45px -10px rgba(0,0,0,0.12);
        }

        .alternar {
            text-align: center;
            color: #168fff;
            cursor: pointer;
            margin-top: 16px;
            text-decoration: underline;
            font-size: 14px;
        }

        .msg {
            padding: 12px;
            margin-bottom: 14px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            display: none;
        }

        .msg-erro  { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg-sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .btn-github-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            color: #555;
            font-size: 13px;
            margin-top: 15px;
            padding: 8px;
            border-radius: 8px;
            background: #f4f6f8;
            transition: 0.2s;
        }
        .btn-github-link:hover {
            background: #e5e9ec;
            color: #000;
        }

        @media (max-width: 360px) {
            .container-login { padding: 20px 16px; }
            .container-login h2 { font-size: 18px; }
        }
    </style>
</head>
<body class="login-page">

<div class="container-login">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="https://i.ibb.co/ymJC5sNN/Captura-de-tela-2026-05-19-100134-1.webp" alt="Logo" width="70" class="icone-redondo">
        <h2>Skedd</h2>
        <p style="margin: 4px 0 0; font-size: 13px; color: #666;">Agenda Escolar Digital (PHP / MySQL)</p>
    </div>

    <div id="alertaErro" class="msg msg-erro"></div>
    <div id="alertaSucesso" class="msg msg-sucesso"></div>

    <!-- BOX DE LOGIN -->
    <div id="box-login">
        <form id="formLogin">
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo getCsrfToken(); ?>">
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" id="loginEmail" placeholder="Digite seu e-mail" required autofocus>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" id="loginSenha" placeholder="Digite sua senha" required>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                <input type="checkbox" id="loginLembrar" style="width: auto; margin: 0; cursor: pointer;">
                <label for="loginLembrar" style="margin: 0; font-size: 14px; font-weight: normal; cursor: pointer; user-select: none;">Manter-me conectado</label>
            </div>
            
            <button type="submit" id="btnEntrar">Entrar</button>
        </form>
        <p class="alternar" onclick="mudarForm(true)">Não tem conta? Cadastre-se</p>
    </div>

    <!-- BOX DE CADASTRO -->
    <div id="box-cadastro" style="display: none;">
        <form id="formCadastro">
            <div class="form-group">
                <label>Tipo de Conta</label>
                <select id="selectNivel">
                    <option value="aluno" selected>Aluno(a)</option>
                    <option value="representante">Representante de Turma</option>
                    <option value="professor">Professor(a)</option>
                </select>
            </div>

            <div class="form-group" id="box-turma">
                <label>Código da Turma</label>
                <input type="text" id="inputTurma" placeholder="Digite o código da sua turma (Ex: 1001)" required>
                <small style="font-size: 11px; color: #888; margin-top: 3px;">Turmas cadastradas: 1001, 2001, 3001, 21321, 272342, 1231313</small>
            </div>

            <div class="form-group">
                <label>Número de Matrícula</label>
                <input type="text" id="inputMatricula" placeholder="Ex: 2026001" required>
            </div>
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" id="inputNome" placeholder="Digite o seu nome completo" required>
            </div>

            <div class="form-group">
                <label>E-mail</label>
                <input type="email" id="inputEmail" placeholder="Digite seu e-mail" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" id="inputSenha" placeholder="Crie uma senha (Criptografia Segura)" required>
            </div>

            <div class="form-group" id="box-chave" style="display: none;">
                <label>Chave de Validação da Escola</label>
                <input type="text" id="inputChave" placeholder="Código institucional (Dica: SKEDE123)">
            </div>

            <button type="submit" id="btnCadastrar">Cadastrar</button>
        </form>
        <p class="alternar" onclick="mudarForm(false)">Já tem conta? Faça Login</p>
    </div>

    <div style="margin-top: 20px; text-align: center; border-top: 1px solid #eee; padding-top: 12px;">
        <a href="https://github.com/gustavo7771/Skedd-Agenda-estudantil-online" target="_blank" rel="noopener noreferrer" class="btn-github-link">
            <svg height="16" width="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>
            <span>Repositório no GitHub & Licenças</span>
        </a>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="skedd-store.js"></script>

<script>
    function exibirErro(msg) {
        $('#alertaSucesso').hide();
        $('#alertaErro').text(msg).fadeIn();
    }

    function exibirSucesso(msg) {
        $('#alertaErro').hide();
        $('#alertaSucesso').text(msg).fadeIn();
    }

    function mudarForm(mostrarCadastro) {
        $('#alertaErro').hide();
        $('#alertaSucesso').hide();
        if (mostrarCadastro) {
            $('#box-login').hide();
            $('#box-cadastro').show();
        } else {
            $('#box-cadastro').hide();
            $('#box-login').show();
        }
    }

    $('#selectNivel').change(function() {
        var nivel = $(this).val();
        if (nivel === 'professor') {
            $('#box-turma').hide();
            $('#inputTurma').removeAttr('required');
            $('#box-chave').show();
            $('#inputChave').attr('required', true);
        } else if (nivel === 'representante') {
            $('#box-turma').show();
            $('#inputTurma').attr('required', true);
            $('#box-chave').show();
            $('#inputChave').attr('required', true);
        } else {
            $('#box-turma').show();
            $('#inputTurma').attr('required', true);
            $('#box-chave').hide();
            $('#inputChave').removeAttr('required');
        }
    });

    // SUBMIT LOGIN
    $('#formLogin').submit(function (e) {
        e.preventDefault();
        var email = $('#loginEmail').val();
        var senha = $('#loginSenha').val();
        var lembrar = $('#loginLembrar').is(':checked');

        var res = SkeddStore.login(email, senha, lembrar);
        if (res.success) {
            window.location.href = 'agenda.php';
        } else {
            exibirErro(res.message);
        }
    });

    // SUBMIT CADASTRO
    $('#formCadastro').submit(function (e) {
        e.preventDefault();
        var dados = {
            nivel: $('#selectNivel').val(),
            turma: $('#inputTurma').val(),
            matricula: $('#inputMatricula').val(),
            nome: $('#inputNome').val(),
            email: $('#inputEmail').val(),
            senha: $('#inputSenha').val(),
            chave: $('#inputChave').val()
        };

        var res = SkeddStore.cadastrar(dados);
        if (res.success) {
            exibirSucesso(res.message);
            mudarForm(false);
            $('#loginEmail').val(dados.email);
            $('#loginSenha').val('');
        } else {
            exibirErro(res.message);
        }
    });
</script>
</body>
</html>

