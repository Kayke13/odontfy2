<?php
session_start();
require_once 'config/database.php';
require_once 'config/upload.php';

$error = '';
$success = '';
$agendamentos = [];
$cliente_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if ($telefone && $email) {
        // Buscar agendamentos do cliente
        $todos_agendamentos = Database::get('agendamentos');
        
        if (empty($todos_agendamentos)) {
            $error = 'Nenhum agendamento encontrado no sistema.';
        } else {
            // Buscar com comparação mais flexível
            $agendamentos_cliente = array_filter($todos_agendamentos, function($agendamento) use ($telefone, $email) {
                if (!isset($agendamento['paciente_telefone']) || !isset($agendamento['paciente_email'])) {
                    return false;
                }
                
                $telefone_normalizado = preg_replace('/[^0-9]/', '', trim($telefone));
                $agendamento_telefone_normalizado = preg_replace('/[^0-9]/', '', trim($agendamento['paciente_telefone']));
                
                $email_normalizado = strtolower(trim($email));
                $agendamento_email_normalizado = strtolower(trim($agendamento['paciente_email']));
                
                return $agendamento_telefone_normalizado === $telefone_normalizado && 
                       $agendamento_email_normalizado === $email_normalizado;
            });
            
        if (!empty($agendamentos_cliente)) {
            usort($agendamentos_cliente, function($a, $b) {
                return strtotime($a['data']) - strtotime($b['data']);
            });
            
            $agendamentos = $agendamentos_cliente;
            
            $primeiro_agendamento = reset($agendamentos_cliente);
            $dentista = Database::find('dentistas', 'id', $primeiro_agendamento['dentista_id']);
            
            $clientes_file = 'data/clientes.json';
            $cliente_key = $primeiro_agendamento['paciente_email'] . '_' . $primeiro_agendamento['paciente_telefone'];
            $cliente_data = [];
            
            if (file_exists($clientes_file)) {
                $clientes = json_decode(file_get_contents($clientes_file), true) ?: [];
                $cliente_data = $clientes[$cliente_key] ?? [];
            }
            
            $cliente_info = [
                'nome' => $primeiro_agendamento['paciente_nome'],
                'email' => $primeiro_agendamento['paciente_email'],
                'telefone' => $primeiro_agendamento['paciente_telefone'],
                'dentista' => $dentista,
                'foto' => $cliente_data['foto'] ?? '',
                'data_nascimento' => $cliente_data['data_nascimento'] ?? '',
                'genero' => $cliente_data['genero'] ?? '',
                'observacoes_medicas' => $cliente_data['observacoes_medicas'] ?? ''
            ];
            
            $_SESSION['cliente_logado'] = $cliente_info;
            $_SESSION['cliente_agendamentos'] = $agendamentos;
            } else {
                $error = 'Nenhum agendamento encontrado com esses dados. Verifique se o telefone e email estão corretos.';
            }
        }
    } else {
        $error = 'Por favor, preencha telefone e email.';
    }
}

// Processar ações do cliente
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $agendamento_id = $_POST['agendamento_id'] ?? '';
    
    if ($action === 'cancelar' && $agendamento_id) {
        $result = Database::update('agendamentos', $agendamento_id, ['status' => 'cancelado']);
        if ($result) {
            $success = 'Agendamento cancelado com sucesso!';
            header('Location: cliente.php');
            exit;
        } else {
            $error = 'Erro ao cancelar agendamento.';
        }
    } elseif ($action === 'editar' && $agendamento_id) {
        $novo_servico = $_POST['novo_servico'] ?? '';
        $nova_data = $_POST['nova_data'] ?? '';
        $nova_hora = $_POST['nova_hora'] ?? '';
        $novas_observacoes = $_POST['novas_observacoes'] ?? '';
        
        if ($novo_servico && $nova_data && $nova_hora) {
            $data_completa = $nova_data . ' ' . $nova_hora;
            $result = Database::update('agendamentos', $agendamento_id, [
                'servico' => $novo_servico,
                'data' => $data_completa,
                'observacoes' => $novas_observacoes,
                'status' => 'pendente'
            ]);
            
            if ($result) {
                $success = 'Agendamento atualizado com sucesso!';
                header('Location: cliente.php');
                exit;
            } else {
                $error = 'Erro ao atualizar agendamento.';
            }
        } else {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        }
    }
}

// Calcular estatísticas
$stats = [
    'total' => count($agendamentos),
    'pendentes' => 0,
    'confirmados' => 0,
    'concluidos' => 0,
    'cancelados' => 0
];

foreach ($agendamentos as $ag) {
    $stats[$ag['status'] . 's']++;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, h6, p, a, button, input, textarea, select, label, span, div {
            font-family: 'Inter', sans-serif !important;
        }
        
        .fa, .fas, .far, .fal, .fab, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        i.fa, i.fas, i.far, i.fal, i.fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .animate-slide-in {
            animation: slideIn 0.4s ease-out;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 min-h-screen">
    
    <?php if (empty($agendamentos)): ?>
        <!-- Tela de Login -->
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full animate-fade-in">
                <!-- Logo e Título -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-3xl shadow-2xl mb-6 transform hover:scale-105 transition-transform">
                        <i class="fas fa-tooth text-white text-3xl"></i>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Portal do Paciente</h1>
                    <p class="text-gray-600">Acesse seus agendamentos e histórico</p>
                </div>
                
                <!-- Card de Login -->
                <div class="glass-effect rounded-3xl shadow-2xl overflow-hidden">
                    <!-- Header do Card -->
                    <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-8 py-6">
                        <h2 class="text-2xl font-bold text-white text-center">Bem-vindo de volta!</h2>
                        <p class="text-blue-100 text-center text-sm mt-1">Digite suas credenciais para acessar</p>
                    </div>
                    
                    <div class="p-8">
                        <?php if ($error): ?>
                            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg animate-slide-in">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
                                    <p class="text-red-700 text-sm"><?php echo $error; ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-phone text-blue-600 mr-2"></i>
                                    Telefone
                                </label>
                                <input type="tel" name="telefone" required
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors"
                                    placeholder="(11) 99999-9999"
                                    value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
                            </div>
                            
                            <div>
                                <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                    Email
                                </label>
                                <input type="email" name="email" required
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors"
                                    placeholder="seu@email.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <button type="submit" 
                                class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-4 px-6 rounded-xl font-bold text-lg shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                                <i class="fas fa-sign-in-alt mr-2"></i>Acessar Meus Agendamentos
                            </button>
                        </form>
                    </div>
                    
                    <!-- Footer do Card -->
                    <div class="bg-gray-50 px-8 py-4 border-t border-gray-100">
                        <p class="text-center text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Utilize o telefone e email que você cadastrou ao agendar
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Portal do Cliente -->
        <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                
                <!-- Header Moderno -->
                <div class="glass-effect rounded-3xl shadow-2xl mb-8 overflow-hidden animate-fade-in">
                    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 p-8">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-6">
                                <div class="relative">
                                    <img src="<?php echo Upload::getImageUrl($cliente_info['foto'] ?? '', 'cliente', $cliente_info['nome']); ?>" 
                                         alt="Foto do cliente" 
                                         class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-xl">
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-4 border-white"></div>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white mb-1">
                                        Olá, <?php echo htmlspecialchars(explode(' ', $cliente_info['nome'])[0]); ?>! 👋
                                    </h1>
                                    <p class="text-blue-100 flex items-center gap-2">
                                        <i class="fas fa-user-md"></i>
                                        Dr(a). <?php echo htmlspecialchars($cliente_info['dentista']['nome']); ?>
                                    </p>
                                    <p class="text-blue-100 text-sm flex items-center gap-2 mt-1">
                                        <i class="fas fa-building"></i>
                                        <?php echo htmlspecialchars($cliente_info['dentista']['clinica']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex gap-3">
                                <a href="perfil_cliente.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5">
                                    <i class="fas fa-user-edit mr-2"></i>Meu Perfil
                                </a>
                                <a href="cliente.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estatísticas -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-white bg-opacity-50">
                        <div class="text-center p-4 bg-white rounded-2xl shadow-md">
                            <div class="text-3xl font-bold text-blue-600"><?php echo $stats['total']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Total de Consultas</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-2xl shadow-md">
                            <div class="text-3xl font-bold text-yellow-600"><?php echo $stats['pendentes']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Pendentes</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-2xl shadow-md">
                            <div class="text-3xl font-bold text-green-600"><?php echo $stats['confirmados']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Confirmadas</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-2xl shadow-md">
                            <div class="text-3xl font-bold text-blue-600"><?php echo $stats['concluidos']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Concluídas</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="mb-6 glass-effect border-l-4 border-green-500 p-4 rounded-2xl shadow-lg animate-slide-in">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                            <p class="text-green-700 font-medium"><?php echo $success; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="mb-6 glass-effect border-l-4 border-red-500 p-4 rounded-2xl shadow-lg animate-slide-in">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
                            <p class="text-red-700 font-medium"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Agendamentos -->
                <div class="glass-effect rounded-3xl shadow-2xl p-8 animate-fade-in">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-white text-xl"></i>
                            </div>
                            Meus Agendamentos
                        </h2>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($agendamentos as $index => $agendamento): ?>
                            <?php
                            $is_past = strtotime($agendamento['data']) < time();
                            $status_config = [
                                'pendente' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => 'fa-clock', 'icon_color' => 'text-yellow-500'],
                                'confirmado' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'badge' => 'bg-green-100 text-green-800 border-green-200', 'icon' => 'fa-check-circle', 'icon_color' => 'text-green-500'],
                                'cancelado' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'badge' => 'bg-red-100 text-red-800 border-red-200', 'icon' => 'fa-times-circle', 'icon_color' => 'text-red-500'],
                                'concluido' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'badge' => 'bg-blue-100 text-blue-800 border-blue-200', 'icon' => 'fa-check-double', 'icon_color' => 'text-blue-500']
                            ];
                            $status = $status_config[$agendamento['status']] ?? $status_config['pendente'];
                            ?>
                            <div class="border-2 <?php echo $status['border']; ?> <?php echo $status['bg']; ?> rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 animate-slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                <div class="p-6">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                                        <!-- Info Principal -->
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                                <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                                    <i class="fas fa-tooth text-blue-600"></i>
                                                    <?php echo htmlspecialchars($agendamento['servico']); ?>
                                                </h3>
                                                <span class="px-4 py-1.5 text-sm font-bold rounded-full border-2 <?php echo $status['badge']; ?> flex items-center gap-2">
                                                    <i class="fas <?php echo $status['icon']; ?>"></i>
                                                    <?php echo ucfirst($agendamento['status']); ?>
                                                </span>
                                                <?php if ($is_past && $agendamento['status'] !== 'concluido' && $agendamento['status'] !== 'cancelado'): ?>
                                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                                        <i class="far fa-clock mr-1"></i>Expirado
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="flex items-center gap-3 text-gray-700">
                                                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                                        <i class="fas fa-calendar text-blue-600"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs text-gray-500 font-medium">Data</div>
                                                        <div class="font-semibold"><?php echo date('d/m/Y', strtotime($agendamento['data'])); ?></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center gap-3 text-gray-700">
                                                    <div class="w-10 h-10 bg-cyan-100 rounded-xl flex items-center justify-center">
                                                        <i class="fas fa-clock text-cyan-600"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs text-gray-500 font-medium">Horário</div>
                                                        <div class="font-semibold"><?php echo date('H:i', strtotime($agendamento['data'])); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($agendamento['observacoes'])): ?>
                                                <div class="mt-4 p-4 bg-white bg-opacity-70 rounded-xl border border-gray-200">
                                                    <div class="flex items-start gap-2">
                                                        <i class="fas fa-comment-alt text-gray-400 mt-1"></i>
                                                        <div>
                                                            <div class="text-xs font-semibold text-gray-500 mb-1">Observações:</div>
                                                            <p class="text-sm text-gray-700"><?php echo htmlspecialchars($agendamento['observacoes']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Ações -->
                                        <div class="flex lg:flex-col gap-3 lg:items-end">
                                            <?php if ($agendamento['status'] === 'pendente' && !$is_past): ?>
                                                <button onclick="toggleEditForm('<?php echo $agendamento['id']; ?>')" 
                                                    class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5">
                                                    <i class="fas fa-edit mr-2"></i>Editar
                                                </button>
                                                <form method="POST" class="flex-1 lg:flex-none" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                                    <input type="hidden" name="action" value="cancelar">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5">
                                                        <i class="fas fa-times mr-2"></i>Cancelar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Formulário de Edição -->
                                    <div id="edit-form-<?php echo $agendamento['id']; ?>" class="hidden mt-6 pt-6 border-t-2 border-gray-200">
                                        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                            <i class="fas fa-edit text-blue-600"></i>
                                            Editar Agendamento
                                        </h4>
                                        <form method="POST" class="space-y-4">
                                            <input type="hidden" name="action" value="editar">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Serviço</label>
                                                <select name="novo_servico" required
                                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                                    <?php foreach ($cliente_info['dentista']['servicos'] as $servico): ?>
                                                        <?php if ($servico['ativo']): ?>
                                                            <option value="<?php echo htmlspecialchars($servico['nome']); ?>" 
                                                                <?php echo ($agendamento['servico'] === $servico['nome']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($servico['nome']); ?>
                                                                (<?php echo $servico['duracao']; ?> min)
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nova Data</label>
                                                    <input type="date" name="nova_data" required
                                                        value="<?php echo date('Y-m-d', strtotime($agendamento['data'])); ?>"
                                                        min="<?php echo date('Y-m-d'); ?>"
                                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Novo Horário</label>
                                                    <input type="time" name="nova_hora" required
                                                        value="<?php echo date('H:i', strtotime($agendamento['data'])); ?>"
                                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                                                <textarea name="novas_observacoes" rows="3"
                                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors"
                                                    placeholder="Observações sobre a consulta..."><?php echo htmlspecialchars($agendamento['observacoes']); ?></textarea>
                                            </div>
                                            
                                            <div class="flex justify-end gap-3">
                                                <button type="button" onclick="toggleEditForm('<?php echo $agendamento['id']; ?>')" 
                                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                                    Cancelar
                                                </button>
                                                <button type="submit" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg">
                                                    <i class="fas fa-save mr-2"></i>Salvar Alterações
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Informações de Contato -->
                <div class="mt-8 glass-effect rounded-3xl shadow-2xl p-8 animate-fade-in">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-600 to-teal-600 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-phone text-white text-xl"></i>
                        </div>
                        Informações de Contato
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start gap-4 p-4 bg-white rounded-2xl shadow-md">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-phone text-blue-600"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 font-medium mb-1">Telefone</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($cliente_info['dentista']['telefone']); ?></div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 bg-white rounded-2xl shadow-md">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-green-600"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 font-medium mb-1">Endereço</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($cliente_info['dentista']['endereco']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        function toggleEditForm(agendamentoId) {
            const editForm = document.getElementById('edit-form-' + agendamentoId);
            if (editForm.classList.contains('hidden')) {
                editForm.classList.remove('hidden');
                editForm.classList.add('animate-slide-in');
            } else {
                editForm.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
