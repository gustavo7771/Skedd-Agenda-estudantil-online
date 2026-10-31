<?php
/**
 * SKEDD - API RESTful PHP / MySQL
 * Repositório: https://github.com/gustavo7771/Skedd-Agenda-estudantil-online
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Crypto.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Captura input via JSON ou via POST/GET
$inputRaw = file_get_contents('php://input');
$inputJson = json_decode($inputRaw, true) ?: [];
$dadosRequisicao = array_merge($_GET, $_POST, $inputJson);

$acao = isset($dadosRequisicao['acao']) ? trim($dadosRequisicao['acao']) : '';
$pdo = Database::getConnection();

function respostaJson($sucesso, $mensagem = '', $dados = []) {
    echo json_encode(array_merge([
        'success' => $sucesso,
        'message' => $mensagem
    ], $dados), JSON_UNESCAPED_UNICODE);
    exit;
}

// Log de auditoria
function registrarLogAuditoria($pdo, $acaoNome, $detalhes, $usuarioId = null) {
    if (!$pdo) return;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO logs (data, acao, detalhes, usuario_id, ip) VALUES (NOW(), ?, ?, ?, ?)");
        $stmt->execute([$acaoNome, $detalhes, $usuarioId, $ip]);
    } catch (Exception $e) {
        error_log("Erro ao gravar log: " . $e->getMessage());
    }
}

// Verifica se banco está conectado
if (!$pdo && !in_array($acao, ['status_db', 'info'])) {
    respostaJson(false, 'Não foi possível conectar ao banco de dados MySQL: ' . Database::getErro(), ['db_offline' => true]);
}

// -------------------------------------------------------------
// ROTEAMENTO DE AÇÕES
// -------------------------------------------------------------
switch ($acao) {

    // ==========================================
    // 1. INFORMAÇÕES E STATUS DO SISTEMA
    // ==========================================
    case 'info':
    case 'status_db':
        respostaJson(true, 'Sistema operacional', [
            'db_conectado' => Database::isConectado(),
            'repositorio'  => SKEDD_GITHUB_REPO,
            'versao'       => '2.5.0-PHP-MySQL',
            'licenca'      => 'MIT License'
        ]);
        break;

    // ==========================================
    // 2. AUTENTICAÇÃO E SESSÃO
    // ==========================================
    case 'usuario_atual':
        if (!empty($_SESSION['skedd_usuario'])) {
            $sessao = $_SESSION['skedd_usuario'];
            // Atualiza dados da sessão direto do banco se for usuário normal
            if (!empty($sessao['id']) && $sessao['id'] !== 'admin_panel') {
                $stmt = $pdo->prepare("SELECT id, matricula, nome, email, nivel, turma, turmas_prof, cargo FROM usuarios WHERE id = ?");
                $stmt->execute([$sessao['id']]);
                $u = $stmt->fetch();
                if ($u) {
                    $u['turmas'] = !empty($u['turmas_prof']) ? json_decode($u['turmas_prof'], true) : [];
                    $_SESSION['skedd_usuario'] = array_merge($sessao, $u);
                    respostaJson(true, 'Sessão ativa', ['user' => $_SESSION['skedd_usuario']]);
                }
            }
            respostaJson(true, 'Sessão ativa', ['user' => $sessao]);
        }

        // Checagem de Cookie "Lembrar de Mim"
        if (!empty($_COOKIE['skedd_lembrar_token'])) {
            $token = $_COOKIE['skedd_lembrar_token'];
            $stmt = $pdo->prepare("SELECT id, matricula, nome, email, nivel, turma, turmas_prof, cargo FROM usuarios WHERE lembrar_token = ?");
            $stmt->execute([$token]);
            $u = $stmt->fetch();
            if ($u) {
                $u['turmas'] = !empty($u['turmas_prof']) ? json_decode($u['turmas_prof'], true) : [];
                $_SESSION['skedd_usuario'] = $u;
                respostaJson(true, 'Autenticado via token persistente', ['user' => $u]);
            }
        }

        respostaJson(false, 'Nenhum usuário logado', ['user' => null]);
        break;

    case 'login':
        $email = strtolower(trim($dadosRequisicao['email'] ?? ''));
        $senha = trim($dadosRequisicao['senha'] ?? '');
        $lembrar = !empty($dadosRequisicao['lembrar']);

        if (empty($email) || empty($senha)) {
            respostaJson(false, 'Preencha todos os campos obrigatórios!');
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !Crypto::verificarSenha($senha, $u['senha'])) {
            respostaJson(false, 'E-mail ou senha incorretos!');
        }

        $turmasProf = !empty($u['turmas_prof']) ? json_decode($u['turmas_prof'], true) : [];
        $sessao = [
            'id'            => $u['id'],
            'usuario_id'    => $u['id'],
            'email'         => $u['email'],
            'nome'          => $u['nome'],
            'matricula'     => $u['matricula'],
            'turma'         => $u['turma'],
            'nivel'         => $u['nivel'],
            'usuario_nivel' => $u['nivel'],
            'turmas'        => $turmasProf
        ];

        $_SESSION['skedd_usuario'] = $sessao;

        if ($lembrar) {
            $token = Crypto::gerarToken(32);
            $stmtUp = $pdo->prepare("UPDATE usuarios SET lembrar_token = ? WHERE id = ?");
            $stmtUp->execute([$token, $u['id']]);
            setcookie('skedd_lembrar_token', $token, [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        registrarLogAuditoria($pdo, "Login de Usuário", "Usuário '{$u['nome']}' ({$u['email']}) efetuou login.", $u['id']);
        respostaJson(true, 'Login realizado com sucesso!', ['user' => $sessao]);
        break;

    case 'login_admin':
        $nome = trim($dadosRequisicao['nome'] ?? '');
        $cargo = trim($dadosRequisicao['cargo'] ?? '');
        $senha = trim($dadosRequisicao['senha'] ?? '');

        if (empty($nome) || empty($cargo) || empty($senha)) {
            respostaJson(false, 'Preencha todos os campos obrigatórios!');
        }

        // Valida contra senha mestre do admin ou administradores cadastrados
        $valido = ($senha === SKEDD_SENHA_ADMIN_MASTER);
        if (!$valido) {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nivel = 'admin' AND senha = ?");
            $stmt->execute([$senha]);
            if ($stmt->rowCount() > 0) {
                $valido = true;
            }
        }

        if (!$valido) {
            respostaJson(false, 'Código de Acesso Admin incorreto!');
        }

        // Coleta todas as turmas para acesso do admin
        $stmtTurmas = $pdo->query("SELECT codigo FROM turmas");
        $todasTurmas = $stmtTurmas->fetchAll(PDO::FETCH_COLUMN);

        $sessaoAdmin = [
            'id'                => 'admin_panel',
            'usuario_id'        => 'admin_panel',
            'admin_autenticado' => true,
            'nome'              => $nome,
            'admin_nome'        => $nome,
            'cargo'             => $cargo,
            'admin_cargo'       => $cargo,
            'nivel'             => 'admin',
            'usuario_nivel'     => 'admin',
            'turma'             => 'Docente/Admin',
            'turmas'            => $todasTurmas
        ];

        $_SESSION['skedd_usuario'] = $sessaoAdmin;
        registrarLogAuditoria($pdo, "Login Administrativo", "Administrador '{$nome} ({$cargo})' acessou o painel.", 'admin_panel');
        respostaJson(true, 'Acesso administrativo autorizado!', ['user' => $sessaoAdmin]);
        break;

    case 'cadastrar':
        $nivel = $dadosRequisicao['nivel'] ?? 'aluno';
        $email = strtolower(trim($dadosRequisicao['email'] ?? ''));
        $senha = trim($dadosRequisicao['senha'] ?? '');
        $nome = trim($dadosRequisicao['nome'] ?? '');
        $matricula = trim($dadosRequisicao['matricula'] ?? '');
        $turma = ($nivel === 'professor') ? 'Docente' : trim($dadosRequisicao['turma'] ?? '');
        $chave = trim($dadosRequisicao['chave'] ?? '');

        if (empty($email) || empty($senha) || empty($nome) || empty($matricula)) {
            respostaJson(false, 'Por favor, preencha todos os campos obrigatórios!');
        }

        // Valida existência da turma para alunos e representantes
        if ($nivel !== 'professor') {
            $stmtT = $pdo->prepare("SELECT codigo FROM turmas WHERE codigo = ?");
            $stmtT->execute([$turma]);
            if ($stmtT->rowCount() === 0) {
                respostaJson(false, 'O código de turma digitado não existe no sistema!');
            }
        }

        // Validação da chave institucional
        if (($nivel === 'professor' || $nivel === 'representante') && $chave !== SKEDD_CHAVE_INSTITUCIONAL) {
            respostaJson(false, 'Chave de validação institucional incorreta!');
        }

        // Verifica unicidade de e-mail e matrícula
        $stmtCheck = $pdo->prepare("SELECT id, email, matricula FROM usuarios WHERE email = ? OR matricula = ?");
        $stmtCheck->execute([$email, $matricula]);
        $existente = $stmtCheck->fetch();

        if ($existente) {
            if (strtolower($existente['email']) === $email) {
                respostaJson(false, 'Este e-mail já está cadastrado no sistema!');
            }
            if ($existente['matricula'] === $matricula) {
                respostaJson(false, 'Este número de matrícula já está cadastrado!');
            }
        }

        $novoId = 'u_' . dechex(time()) . '_' . bin2hex(random_bytes(3));
        $senhaHash = Crypto::hashSenha($senha);
        $turmasProfJson = ($nivel === 'professor') ? json_encode([]) : null;

        $stmtIns = $pdo->prepare("INSERT INTO usuarios (id, matricula, nome, email, senha, nivel, turma, turmas_prof) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([$novoId, $matricula, $nome, $email, $senhaHash, $nivel, $turma, $turmasProfJson]);

        registrarLogAuditoria($pdo, "Cadastro de Usuário", "Novo usuário cadastrado: {$nome} ({$nivel} - {$email})", $novoId);

        $msg = "Cadastro efetuado com sucesso! Você já pode entrar.";
        if ($nivel === 'professor') {
            $msg .= " (Professores: solicitem liberação de turmas ao admin)";
        }

        respostaJson(true, $msg, ['usuario_id' => $novoId]);
        break;

    case 'logout':
        if (!empty($_SESSION['skedd_usuario']['id']) && !empty($pdo)) {
            $uid = $_SESSION['skedd_usuario']['id'];
            $stmt = $pdo->prepare("UPDATE usuarios SET lembrar_token = NULL WHERE id = ?");
            $stmt->execute([$uid]);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        setcookie('skedd_lembrar_token', '', time() - 3600, '/');
        respostaJson(true, 'Sessão encerrada com sucesso.');
        break;

    // ==========================================
    // 3. GERENCIAMENTO DE TURMAS
    // ==========================================
    case 'listar_turmas':
        $stmt = $pdo->query("SELECT codigo, apelido FROM turmas ORDER BY codigo ASC");
        $turmasObj = [];
        $turmasLista = [];
        while ($row = $stmt->fetch()) {
            $turmasObj[$row['codigo']] = $row['apelido'];
            $turmasLista[] = $row;
        }
        respostaJson(true, '', ['turmas' => $turmasObj, 'lista' => $turmasLista]);
        break;

    case 'adicionar_turma':
        $codigo = trim($dadosRequisicao['codigo'] ?? '');
        $apelido = trim($dadosRequisicao['apelido'] ?? '') ?: 'Sem Apelido';

        if (empty($codigo)) {
            respostaJson(false, 'O código da turma é obrigatório!');
        }

        $stmt = $pdo->prepare("INSERT INTO turmas (codigo, apelido) VALUES (?, ?) ON DUPLICATE KEY UPDATE apelido = VALUES(apelido)");
        $stmt->execute([$codigo, $apelido]);

        registrarLogAuditoria($pdo, "Criar Turma", "Turma {$codigo} ('{$apelido}') criada/atualizada.");
        respostaJson(true, "Turma criada com sucesso!");
        break;

    case 'editar_turma':
        $codigo = trim($dadosRequisicao['codigo'] ?? '');
        $novoApelido = trim($dadosRequisicao['apelido'] ?? '');

        if (empty($codigo) || empty($novoApelido)) {
            respostaJson(false, 'Código e novo apelido são obrigatórios!');
        }

        $stmt = $pdo->prepare("UPDATE turmas SET apelido = ? WHERE codigo = ?");
        $stmt->execute([$novoApelido, $codigo]);

        registrarLogAuditoria($pdo, "Editar Turma", "Turma {$codigo} atualizada para '{$novoApelido}'.");
        respostaJson(true, "Apelido da turma atualizado!");
        break;

    case 'excluir_turma':
        $codigo = trim($dadosRequisicao['codigo'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM turmas WHERE codigo = ?");
        $stmt->execute([$codigo]);

        registrarLogAuditoria($pdo, "Excluir Turma", "Turma {$codigo} excluída.");
        respostaJson(true, "Turma excluída com sucesso!");
        break;

    case 'contagem_usuarios':
        $stmtTurmas = $pdo->query("SELECT codigo FROM turmas");
        $contagem = [];
        while ($row = $stmtTurmas->fetch()) {
            $contagem[$row['codigo']] = 0;
        }

        $stmtUsers = $pdo->query("SELECT turma, nivel FROM usuarios");
        $docentes = 0;
        while ($u = $stmtUsers->fetch()) {
            $t = $u['turma'];
            if (isset($contagem[$t])) {
                $contagem[$t]++;
            }
            if ($t === 'Docente' || $t === 'Docente/Admin' || $u['nivel'] === 'professor') {
                $docentes++;
            }
        }
        respostaJson(true, '', ['porTurma' => $contagem, 'docentes' => $docentes]);
        break;

    // ==========================================
    // 4. GERENCIAMENTO DE USUÁRIOS
    // ==========================================
    case 'listar_usuarios':
        $stmt = $pdo->query("SELECT id, matricula, nome, email, nivel, turma, turmas_prof, cargo, criado_em FROM usuarios ORDER BY nome ASC");
        $usuarios = [];

        // Busca disciplinas por professor para associar e exibir na gestão administrativa
        $stmtDisc = $pdo->query("
            SELECT d.professor_id, d.nome as disciplina_nome, d.codigo as disciplina_codigo, d.turma_codigo, t.apelido as turma_nome
            FROM disciplinas d
            LEFT JOIN turmas t ON d.turma_codigo = t.codigo
            WHERE d.professor_id IS NOT NULL AND d.professor_id != ''
        ");
        $discPorProf = [];
        while ($d = $stmtDisc->fetch()) {
            $discPorProf[$d['professor_id']][] = $d;
        }

        while ($u = $stmt->fetch()) {
            $turmasProf = !empty($u['turmas_prof']) ? (is_array($u['turmas_prof']) ? $u['turmas_prof'] : json_decode($u['turmas_prof'], true)) : [];
            if (!is_array($turmasProf)) {
                $turmasProf = [];
            }
            $turmasProf = array_map('strval', $turmasProf);

            // Se for professor e possuir disciplinas vinculadas a turmas, garante que essas turmas estejam inclusas
            if ($u['nivel'] === 'professor' && !empty($discPorProf[$u['id']])) {
                foreach ($discPorProf[$u['id']] as $dItem) {
                    if (!empty($dItem['turma_codigo']) && !in_array(strval($dItem['turma_codigo']), $turmasProf)) {
                        $turmasProf[] = strval($dItem['turma_codigo']);
                    }
                }
            }

            $u['turmas'] = array_values(array_unique($turmasProf));
            $u['disciplinas'] = $discPorProf[$u['id']] ?? [];
            $usuarios[] = $u;
        }
        respostaJson(true, '', ['usuarios' => $usuarios]);
        break;

    case 'salvar_usuario':
        $id = trim($dadosRequisicao['id'] ?? '');
        $email = trim($dadosRequisicao['email'] ?? '');
        $nivel = trim($dadosRequisicao['nivel'] ?? 'aluno');
        $turma = trim($dadosRequisicao['turma'] ?? '');
        $turmasProf = $dadosRequisicao['turmas_prof'] ?? [];
        $novaSenha = trim($dadosRequisicao['nova_senha'] ?? '');

        if (empty($id)) {
            respostaJson(false, 'ID de usuário obrigatório.');
        }

        if ($nivel === 'professor' || $nivel === 'admin') {
            $turma = 'Docente/Admin';
            if (is_string($turmasProf)) {
                $turmasProfDecoded = json_decode($turmasProf, true);
                $turmasProf = is_array($turmasProfDecoded) ? $turmasProfDecoded : (strlen($turmasProf) ? explode(',', $turmasProf) : []);
            }
            if (!is_array($turmasProf)) {
                $turmasProf = [];
            }
            $turmasProfLimpo = array_values(array_unique(array_filter(array_map('strval', $turmasProf))));
            $turmasProfJson = json_encode($turmasProfLimpo, JSON_UNESCAPED_UNICODE);
        } else {
            $turmasProfJson = null;
        }

        if (!empty($novaSenha)) {
            $senhaHash = Crypto::hashSenha($novaSenha);
            $stmt = $pdo->prepare("UPDATE usuarios SET email = ?, nivel = ?, turma = ?, turmas_prof = ?, senha = ? WHERE id = ?");
            $stmt->execute([$email, $nivel, $turma, $turmasProfJson, $senhaHash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET email = ?, nivel = ?, turma = ?, turmas_prof = ? WHERE id = ?");
            $stmt->execute([$email, $nivel, $turma, $turmasProfJson, $id]);
        }

        // Se o usuário atual na sessão PHP for o mesmo editado, atualiza a sessão
        if (!empty($_SESSION['skedd_usuario']) && $_SESSION['skedd_usuario']['id'] === $id) {
            $_SESSION['skedd_usuario']['email'] = $email;
            $_SESSION['skedd_usuario']['nivel'] = $nivel;
            $_SESSION['skedd_usuario']['turma'] = $turma;
            if ($nivel === 'professor' || $nivel === 'admin') {
                $_SESSION['skedd_usuario']['turmas'] = $turmasProfLimpo;
            }
        }

        registrarLogAuditoria($pdo, "Editar Usuário", "O usuário ID {$id} teve suas permissões atualizadas.");
        respostaJson(true, "Usuário salvo com sucesso!");
        break;

    case 'deletar_usuario':
        $id = trim($dadosRequisicao['id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        registrarLogAuditoria($pdo, "Deletar Usuário", "Usuário ID {$id} excluído.");
        respostaJson(true, "Usuário deletado com sucesso!");
        break;

    // ==========================================
    // 5. GERENCIAMENTO DE PROVAS / EVENTOS
    // ==========================================
    case 'listar_provas':
        $turmaFiltro = trim($dadosRequisicao['turma'] ?? '');
        $usuarioAtual = $_SESSION['skedd_usuario'] ?? null;
        $nivel = $usuarioAtual['nivel'] ?? 'aluno';
        $userId = $usuarioAtual['id'] ?? '';

        $sql = "SELECT id, disciplina_id, title, description, start, color, turma, criador_id, is_admin, nome_admin, all_day FROM provas";
        $params = [];

        if ($nivel === 'admin') {
            if (!empty($turmaFiltro) && $turmaFiltro !== 'todas') {
                $sql .= " WHERE turma = ? OR turma = 'todas'";
                $params = [$turmaFiltro];
            }
        } elseif ($nivel === 'professor') {
            if (!empty($turmaFiltro) && $turmaFiltro !== 'todas') {
                $sql .= " WHERE turma = ? OR turma = 'todas'";
                $params = [$turmaFiltro];
            } else {
                $turmasProf = $usuarioAtual['turmas'] ?? [];
                if (!empty($turmasProf)) {
                    $inTurmas = implode(',', array_fill(0, count($turmasProf), '?'));
                    $sql .= " WHERE criador_id = ? OR turma = 'todas' OR turma IN ($inTurmas)";
                    $params = array_merge([$userId], $turmasProf);
                } else {
                    $sql .= " WHERE criador_id = ? OR turma = 'todas'";
                    $params = [$userId];
                }
            }
        } else {
            // Aluno ou Representante
            $turmaAluno = $usuarioAtual['turma'] ?? $turmaFiltro;
            $sql .= " WHERE turma = ? OR turma = 'todas'";
            $params = [$turmaAluno];
        }

        $sql .= " ORDER BY start ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $provas = $stmt->fetchAll();

        // Formata para o FullCalendar
        foreach ($provas as &$p) {
            $p['allDay'] = (bool)$p['all_day'];
            $p['is_admin'] = (bool)$p['is_admin'];
        }

        respostaJson(true, '', ['provas' => $provas]);
        break;

    case 'salvar_prova':
        $nome = trim($dadosRequisicao['nome'] ?? $dadosRequisicao['title'] ?? '');
        $data = trim($dadosRequisicao['data'] ?? $dadosRequisicao['start'] ?? '');
        $cor = trim($dadosRequisicao['cor'] ?? $dadosRequisicao['col'] ?? $dadosRequisicao['color'] ?? '#168fff');
        $descricao = trim($dadosRequisicao['descricao'] ?? $dadosRequisicao['description'] ?? '');
        $turma = trim($dadosRequisicao['turma'] ?? '');
        $nomeAdmin = trim($dadosRequisicao['nome_admin'] ?? '');
        $disciplinaId = !empty($dadosRequisicao['disciplina_id']) ? intval($dadosRequisicao['disciplina_id']) : null;

        $usuarioAtual = $_SESSION['skedd_usuario'] ?? null;
        $criadorId = $usuarioAtual['id'] ?? 'admin_panel';
        $isAdmin = ($usuarioAtual['nivel'] ?? '') === 'admin' ? 1 : 0;
        $isProfessor = ($usuarioAtual['nivel'] ?? '') === 'professor';

        if (empty($nome) || empty($data) || empty($turma)) {
            respostaJson(false, 'Campos obrigatórios ausentes para salvar a avaliação!');
        }

        if ($isProfessor && empty($disciplinaId)) {
            respostaJson(false, 'É obrigatório selecionar uma disciplina para marcar a avaliação!');
        }

        // Se for publicação para múltiplas turmas ou todas
        $stmtTurmas = $pdo->query("SELECT codigo, apelido FROM turmas");
        $apelidos = $stmtTurmas->fetchAll(PDO::FETCH_KEY_PAIR);

        $turmasParaInserir = [];
        if ($turma === 'todas') {
            if ($isAdmin) {
                $turmasParaInserir[] = 'todas';
            } else {
                $turmasProf = $usuarioAtual['turmas'] ?? [];
                if (empty($turmasProf)) {
                    $turmasProf = array_keys($apelidos);
                }
                $turmasParaInserir = $turmasProf;
            }
        } else {
            $turmasParaInserir = [$turma];
        }

        $stmtIns = $pdo->prepare("INSERT INTO provas (id, disciplina_id, title, description, start, color, turma, criador_id, is_admin, nome_admin, all_day) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($turmasParaInserir as $t) {
            $apelidoExib = ($t === 'todas') ? 'Todas as Turmas' : ($apelidos[$t] ?? $t);
            $tituloCompleto = $nome . " (" . $apelidoExib . ")";
            $novoId = 'p_' . dechex(time()) . '_' . bin2hex(random_bytes(3));

            $stmtIns->execute([
                $novoId,
                $disciplinaId,
                $tituloCompleto,
                $descricao,
                $data,
                $cor,
                $t,
                $criadorId,
                $isAdmin,
                $isAdmin ? ($nomeAdmin ?: 'Administração') : ''
            ]);
        }

        registrarLogAuditoria($pdo, "Criar Evento", "Evento '{$nome}' cadastrado para a data {$data} ({$turma}).", $criadorId);
        respostaJson(true, "Avaliação gravada com sucesso no MySQL!");
        break;

    case 'editar_prova':
        $id = trim($dadosRequisicao['id'] ?? '');
        $nome = trim($dadosRequisicao['nome'] ?? $dadosRequisicao['title'] ?? '');
        $data = trim($dadosRequisicao['data'] ?? $dadosRequisicao['start'] ?? '');
        $cor = trim($dadosRequisicao['cor'] ?? $dadosRequisicao['col'] ?? $dadosRequisicao['color'] ?? '#168fff');
        $descricao = trim($dadosRequisicao['descricao'] ?? $dadosRequisicao['description'] ?? '');
        $turma = trim($dadosRequisicao['turma'] ?? '');
        $nomeAdmin = trim($dadosRequisicao['nome_admin'] ?? '');
        $disciplinaId = !empty($dadosRequisicao['disciplina_id']) ? intval($dadosRequisicao['disciplina_id']) : null;

        $usuarioAtual = $_SESSION['skedd_usuario'] ?? null;
        $isProfessor = ($usuarioAtual['nivel'] ?? '') === 'professor';

        if (empty($id) || empty($nome) || empty($data) || empty($turma)) {
            respostaJson(false, 'Dados incompletos para edição da avaliação.');
        }

        if ($isProfessor && empty($disciplinaId)) {
            respostaJson(false, 'É obrigatório selecionar uma disciplina para a avaliação!');
        }

        $stmtT = $pdo->prepare("SELECT apelido FROM turmas WHERE codigo = ?");
        $stmtT->execute([$turma]);
        $rowT = $stmtT->fetch();
        $turmaNome = ($turma === 'todas') ? 'Todas as Turmas' : ($rowT['apelido'] ?? $turma);
        $tituloCompleto = $nome . " (" . $turmaNome . ")";

        $stmtUp = $pdo->prepare("UPDATE provas SET title = ?, description = ?, start = ?, color = ?, turma = ?, disciplina_id = COALESCE(?, disciplina_id), nome_admin = COALESCE(NULLIF(?, ''), nome_admin) WHERE id = ?");
        $stmtUp->execute([$tituloCompleto, $descricao, $data, $cor, $turma, $disciplinaId, $nomeAdmin, $id]);

        registrarLogAuditoria($pdo, "Editar Evento", "Evento ID {$id} atualizado.");
        respostaJson(true, "Evento atualizado com sucesso!");
        break;

    case 'deletar_prova':
        $id = trim($dadosRequisicao['id'] ?? '');
        if (empty($id)) {
            respostaJson(false, 'ID do evento ausente.');
        }

        $stmt = $pdo->prepare("DELETE FROM provas WHERE id = ?");
        $stmt->execute([$id]);

        registrarLogAuditoria($pdo, "Excluir Evento", "Evento ID {$id} excluído do banco.");
        respostaJson(true, "Avaliação excluída com sucesso!");
        break;

    // ==========================================
    // 6. GERENCIAMENTO DE DISCIPLINAS
    // ==========================================
    case 'listar_disciplinas':
        $turma = trim($dadosRequisicao['turma_codigo'] ?? $dadosRequisicao['turma'] ?? '');

        $usuarioSessao  = $_SESSION['skedd_usuario'] ?? null;
        $nivelSessao    = $usuarioSessao['nivel'] ?? 'aluno';
        $professorSessao = $usuarioSessao['id'] ?? null;

        $sql = "
            SELECT 
                d.id, 
                d.codigo, 
                d.nome, 
                d.descricao, 
                d.carga_horaria, 
                d.turma_codigo, 
                d.professor_id, 
                t.apelido AS turma_nome, 
                u.nome AS professor_nome,
                u.email AS professor_email,
                (
                    SELECT COUNT(*) 
                    FROM provas p 
                    WHERE (p.disciplina_id = d.id) 
                       OR (p.disciplina_id IS NULL AND (p.turma = d.turma_codigo OR p.turma = 'todas') AND (
                           p.criador_id = d.professor_id 
                           OR p.title LIKE CONCAT('%', d.nome, '%') 
                           OR (d.codigo IS NOT NULL AND d.codigo != '' AND p.title LIKE CONCAT('%', d.codigo, '%'))
                       ))
                ) AS total_atividades
            FROM disciplinas d
            LEFT JOIN turmas t ON d.turma_codigo = t.codigo
            LEFT JOIN usuarios u ON d.professor_id = u.id
        ";

        $params = [];
        $conditions = [];

        // Filtra por turma específica
        if (!empty($turma)) {
            $conditions[] = "(d.turma_codigo = ? OR d.turma_codigo IS NULL OR d.turma_codigo = '' OR d.turma_codigo = 'todas')";
            $params[] = $turma;
        }

        // Se for professor logado, filtra apenas suas disciplinas
        if ($nivelSessao === 'professor' && !empty($professorSessao)) {
            $conditions[] = "d.professor_id = ?";
            $params[] = $professorSessao;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY d.nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $disciplinas = $stmt->fetchAll();
        respostaJson(true, '', ['disciplinas' => $disciplinas]);
        break;

    case 'salvar_disciplina':
        $id = !empty($dadosRequisicao['id']) ? intval($dadosRequisicao['id']) : null;
        $codigo = trim($dadosRequisicao['codigo'] ?? '');
        $nome = trim($dadosRequisicao['nome'] ?? '');
        $descricao = trim($dadosRequisicao['descricao'] ?? '');
        $carga = intval($dadosRequisicao['carga_horaria'] ?? 0);
        $turma = trim($dadosRequisicao['turma_codigo'] ?? '');
        $professorId = trim($dadosRequisicao['professor_id'] ?? '');

        if (empty($nome)) {
            respostaJson(false, 'O nome da disciplina é obrigatório!');
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE disciplinas SET codigo = ?, nome = ?, descricao = ?, carga_horaria = ?, turma_codigo = ?, professor_id = ? WHERE id = ?");
            $stmt->execute([$codigo, $nome, $descricao, $carga, $turma, $professorId, $id]);
            registrarLogAuditoria($pdo, "Editar Disciplina", "Disciplina '{$nome}' (ID {$id}) atualizada.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO disciplinas (codigo, nome, descricao, carga_horaria, turma_codigo, professor_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nome, $descricao, $carga, $turma, $professorId]);
            registrarLogAuditoria($pdo, "Criar Disciplina", "Nova disciplina cadastrada: {$nome}.");
        }

        // Se professor e turma estiverem definidos, garante que essa turma conste nas turmas atribuídas do professor
        if (!empty($professorId) && !empty($turma)) {
            $stmtProf = $pdo->prepare("SELECT turmas_prof FROM usuarios WHERE id = ?");
            $stmtProf->execute([$professorId]);
            $profRow = $stmtProf->fetch();
            if ($profRow) {
                $tProfs = !empty($profRow['turmas_prof']) ? json_decode($profRow['turmas_prof'], true) : [];
                if (!is_array($tProfs)) $tProfs = [];
                $tProfs = array_map('strval', $tProfs);
                if (!in_array(strval($turma), $tProfs)) {
                    $tProfs[] = strval($turma);
                    $updProf = $pdo->prepare("UPDATE usuarios SET turmas_prof = ? WHERE id = ?");
                    $updProf->execute([json_encode(array_values($tProfs), JSON_UNESCAPED_UNICODE), $professorId]);
                }
            }
        }

        respostaJson(true, 'Disciplina salva com sucesso!');
        break;

    case 'excluir_disciplina':
        $id = intval($dadosRequisicao['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt->execute([$id]);
        registrarLogAuditoria($pdo, "Excluir Disciplina", "Disciplina ID {$id} removida.");
        respostaJson(true, 'Disciplina removida!');
        break;

    // ==========================================
    // 7. QUESTÕES ADMINISTRATIVAS E COMUNICADOS
    // ==========================================
    case 'listar_questoes':
        $stmt = $pdo->query("SELECT * FROM questoes_administrativas ORDER BY criado_em DESC");
        $questoes = $stmt->fetchAll();
        respostaJson(true, '', ['questoes' => $questoes]);
        break;

    case 'salvar_questao':
        $id = !empty($dadosRequisicao['id']) ? intval($dadosRequisicao['id']) : null;
        $titulo = trim($dadosRequisicao['titulo'] ?? '');
        $descricao = trim($dadosRequisicao['descricao'] ?? '');
        $tipo = trim($dadosRequisicao['tipo'] ?? 'comunicado');
        $prioridade = trim($dadosRequisicao['prioridade'] ?? 'media');
        $turmaAlvo = trim($dadosRequisicao['turma_alvo'] ?? 'todas');
        $resposta = trim($dadosRequisicao['resposta'] ?? '');
        $status = trim($dadosRequisicao['status'] ?? 'aberta');

        $usuarioAtual = $_SESSION['skedd_usuario'] ?? null;
        $autorNome = $usuarioAtual['nome'] ?? 'Administração Skedd';
        $autorId = $usuarioAtual['id'] ?? 'admin_panel';

        if (empty($titulo) || empty($descricao)) {
            respostaJson(false, 'Título e descrição são obrigatórios para a questão administrativa!');
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE questoes_administrativas SET titulo = ?, descricao = ?, tipo = ?, prioridade = ?, turma_alvo = ?, resposta = ?, status = ? WHERE id = ?");
            $stmt->execute([$titulo, $descricao, $tipo, $prioridade, $turmaAlvo, $resposta, $status, $id]);
            registrarLogAuditoria($pdo, "Editar Questão Admin", "Questão ID {$id} atualizada.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO questoes_administrativas (titulo, descricao, tipo, prioridade, status, autor_nome, autor_id, turma_alvo, resposta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $tipo, $prioridade, $status, $autorNome, $autorId, $turmaAlvo, $resposta]);
            registrarLogAuditoria($pdo, "Criar Questão Admin", "Nova questão/comunicado: {$titulo}.");
        }
        respostaJson(true, 'Questão administrativa registrada com sucesso!');
        break;

    case 'atualizar_status_questao':
        $id = intval($dadosRequisicao['id'] ?? 0);
        $status = trim($dadosRequisicao['status'] ?? 'resolvida');
        $resposta = trim($dadosRequisicao['resposta'] ?? '');

        $stmt = $pdo->prepare("UPDATE questoes_administrativas SET status = ?, resposta = COALESCE(NULLIF(?, ''), resposta) WHERE id = ?");
        $stmt->execute([$status, $resposta, $id]);
        registrarLogAuditoria($pdo, "Atualizar Questão Admin", "Questão ID {$id} marcada como {$status}.");
        respostaJson(true, 'Status atualizado com sucesso!');
        break;

    case 'excluir_questao':
        $id = intval($dadosRequisicao['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM questoes_administrativas WHERE id = ?");
        $stmt->execute([$id]);
        registrarLogAuditoria($pdo, "Excluir Questão Admin", "Questão ID {$id} excluída.");
        respostaJson(true, 'Questão administrativa excluída!');
        break;

    // ==========================================
    // 8. LOGS DO SISTEMA E AUDITORIA
    // ==========================================
    case 'listar_logs':
        $stmt = $pdo->query("SELECT id, data, acao, detalhes, usuario_id, ip FROM logs ORDER BY data DESC LIMIT 150");
        $logs = $stmt->fetchAll();
        respostaJson(true, '', ['logs' => $logs]);
        break;

    case 'registrar_log':
        $acaoNome = trim($dadosRequisicao['acao_nome'] ?? 'Ação Geral');
        $detalhes = trim($dadosRequisicao['detalhes'] ?? '');
        $uid = $_SESSION['skedd_usuario']['id'] ?? null;
        registrarLogAuditoria($pdo, $acaoNome, $detalhes, $uid);
        respostaJson(true, 'Log registrado.');
        break;

    // ==========================================
    // 9. RESTAURAR PADRÕES DE FÁBRICA
    // ==========================================
    case 'resetar_padrao':
        $sqlPath = __DIR__ . '/database.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $comandos = explode(';', $sql);
            foreach ($comandos as $cmd) {
                $cmd = trim($cmd);
                if (!empty($cmd) && stripos($cmd, 'CREATE DATABASE') === false && stripos($cmd, 'USE ') !== 0) {
                    try {
                        $pdo->exec($cmd);
                    } catch (Exception $e) {
                        // continua
                    }
                }
            }
            registrarLogAuditoria($pdo, "Reset de Dados", "Dados do sistema restaurados para o padrão de fábrica.");
            respostaJson(true, 'Dados do MySQL restaurados para os padrões com sucesso!');
        } else {
            respostaJson(false, 'Arquivo database.sql não encontrado.');
        }
        break;

    default:
        respostaJson(false, 'Ação não reconhecida pela API.');
        break;
}

