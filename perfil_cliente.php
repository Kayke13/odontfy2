<?php
session_start();
require_once 'config/database.php';
require_once 'config/upload.php';

$error = '';
$success = '';
$cliente_info = null;
$agendamentos = [];

// Verificar se cliente está logado (via telefone/email)
if (isset($_SESSION['cliente_logado'])) {
    $cliente_info = $_SESSION['cliente_logado'];
    $agendamentos = $_SESSION['cliente_agendamentos'] ?? [];
} else {
    // Redirecionar para login se não estiver logado
    header('Location: cliente.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $data_nascimento = trim($_POST['data_nascimento'] ?? '');
        $genero = trim($_POST['genero'] ?? '');
        $observacoes_medicas = trim($_POST['observacoes_medicas'] ?? '');
        
        if ($nome && $email && $telefone) {
            // Atualizar dados nos agendamentos
            $todos_agendamentos = Database::get('agendamentos');
            $agendamentos_atualizados = [];
            
            foreach ($todos_agendamentos as $agendamento) {
                if ($agendamento['paciente_email'] === $cliente_info['email'] && 
                    $agendamento['paciente_telefone'] === $cliente_info['telefone']) {
                    
                    $agendamento['paciente_nome'] = $nome;
                    $agendamento['paciente_email'] = $email;
                    $agendamento['paciente_telefone'] = $telefone;
                }
                $agendamentos_atualizados[] = $agendamento;
            }
            
            // Salvar dados do cliente
            $cliente_data = [
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'data_nascimento' => $data_nascimento,
                'genero' => $genero,
                'observacoes_medicas' => $observacoes_medicas,
                'foto' => $cliente_info['foto'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Salvar no arquivo de clientes (criar se não existir)
            $clientes_file = 'data/clientes.json';
            $clientes = [];
            if (file_exists($clientes_file)) {
                $clientes = json_decode(file_get_contents($clientes_file), true) ?: [];
            }
            
            $cliente_key = $email . '_' . $telefone;
            $clientes[$cliente_key] = $cliente_data;
            
            if (file_put_contents($clientes_file, json_encode($clientes, JSON_PRETTY_PRINT))) {
                // Atualizar agendamentos
                Database::save('agendamentos', $agendamentos_atualizados);
                
                // Atualizar sessão
                $_SESSION['cliente_logado'] = array_merge($cliente_info, $cliente_data);
                $cliente_info = $_SESSION['cliente_logado'];
                
                $success = 'Perfil atualizado com sucesso!';
            } else {
                $error = 'Erro ao salvar perfil.';
            }
        } else {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        }
    } elseif ($action === 'upload_photo') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = Upload::uploadImage($_FILES['foto'], 'cliente');
            
            if ($uploadResult['success']) {
                // Remover foto anterior se existir
                if (!empty($cliente_info['foto'])) {
                    $oldPhoto = 'uploads/clientes/' . $cliente_info['foto'];
                    if (file_exists($oldPhoto)) {
                        unlink($oldPhoto);
                    }
                }
                
                // Atualizar dados do cliente
                $cliente_key = $cliente_info['email'] . '_' . $cliente_info['telefone'];
                $clientes_file = 'data/clientes.json';
                $clientes = [];
                if (file_exists($clientes_file)) {
                    $clientes = json_decode(file_get_contents($clientes_file), true) ?: [];
                }
                
                if (!isset($clientes[$cliente_key])) {
                    $clientes[$cliente_key] = $cliente_info;
                }
                $clientes[$cliente_key]['foto'] = $uploadResult['filename'];
                
                if (file_put_contents($clientes_file, json_encode($clientes, JSON_PRETTY_PRINT))) {
                    // Atualizar sessão
                    $_SESSION['cliente_logado']['foto'] = $uploadResult['filename'];
                    $cliente_info = $_SESSION['cliente_logado'];
                    
                    $success = 'Foto atualizada com sucesso!';
                } else {
                    $error = 'Erro ao salvar foto no banco de dados.';
                }
            } else {
                $error = $uploadResult['message'];
            }
        } else {
            $error = 'Nenhuma foto foi selecionada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Odontfy</title>
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
        
        @keyframes pulse-border {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
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
        
        .profile-image-hover {
            transition: all 0.3s ease;
        }
        
        .profile-image-hover:hover {
            transform: scale(1.05);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 min-h-screen">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            
            <!-- Header -->
            <div class="glass-effect rounded-3xl shadow-2xl mb-8 overflow-hidden animate-fade-in">
                <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 p-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-user-edit text-white text-xl"></i>
                                </div>
                                Editar Meu Perfil
                            </h1>
                            <p class="text-blue-100 mt-2 ml-15">
                                Gerencie suas informações pessoais e preferências
                            </p>
                        </div>
                        <a href="cliente.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Voltar
                        </a>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Sidebar - Foto de Perfil -->
                <div class="lg:col-span-1">
                    <div class="glass-effect rounded-3xl shadow-2xl p-8 animate-fade-in">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <i class="fas fa-camera text-blue-600"></i>
                            Foto de Perfil
                        </h3>
                        
                        <div class="flex flex-col items-center">
                            <div class="relative mb-6">
                                <img src="<?php echo Upload::getImageUrl($cliente_info['foto'] ?? '', 'cliente', $cliente_info['nome']); ?>" 
                                     alt="Foto de perfil" 
                                     class="w-40 h-40 rounded-3xl object-cover border-4 border-blue-200 shadow-xl profile-image-hover"
                                     id="profile-preview">
                                <div class="absolute -bottom-3 -right-3 w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl border-4 border-white shadow-lg flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xl"></i>
                                </div>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data" class="w-full">
                                <input type="hidden" name="action" value="upload_photo">
                                <input type="file" name="foto" id="foto-input" accept="image/*" 
                                       class="hidden" onchange="previewImage(this)">
                                <label for="foto-input" 
                                       class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white px-6 py-4 rounded-xl cursor-pointer flex items-center justify-center gap-2 font-semibold shadow-lg hover:-translate-y-0.5 transition-all">
                                    <i class="fas fa-camera"></i>
                                    Alterar Foto
                                </label>
                            </form>
                            
                            <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-100 w-full">
                                <p class="text-xs text-gray-600 text-center">
                                    <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                    JPG, PNG, GIF, WebP<br>
                                    Tamanho máximo: 5MB
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Card -->
                    <div class="glass-effect rounded-3xl shadow-2xl p-6 mt-6 animate-fade-in" style="animation-delay: 0.2s">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-user-shield text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 mb-2">Seus Dados Estão Seguros</h4>
                            <p class="text-sm text-gray-600">
                                Todas as suas informações são criptografadas e armazenadas com segurança.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulário Principal -->
                <div class="lg:col-span-2">
                    <div class="glass-effect rounded-3xl shadow-2xl p-8 animate-fade-in" style="animation-delay: 0.1s">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <i class="fas fa-id-card text-blue-600"></i>
                            Informações Pessoais
                        </h3>
                        
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <!-- Nome e Email -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user text-blue-600 mr-2"></i>
                                        Nome Completo *
                                    </label>
                                    <input type="text" name="nome" required
                                        value="<?php echo htmlspecialchars($cliente_info['nome'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                        Email *
                                    </label>
                                    <input type="email" name="email" required
                                        value="<?php echo htmlspecialchars($cliente_info['email'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>
                            </div>

                            <!-- Telefone e Data de Nascimento -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-phone text-blue-600 mr-2"></i>
                                        Telefone *
                                    </label>
                                    <input type="tel" name="telefone" required
                                        value="<?php echo htmlspecialchars($cliente_info['telefone'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-birthday-cake text-blue-600 mr-2"></i>
                                        Data de Nascimento
                                    </label>
                                    <input type="date" name="data_nascimento"
                                        value="<?php echo htmlspecialchars($cliente_info['data_nascimento'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>
                            </div>

                            <!-- Gênero -->
                            <div>
                                <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-venus-mars text-blue-600 mr-2"></i>
                                    Gênero
                                </label>
                                <select name="genero" 
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="">Selecione</option>
                                    <option value="masculino" <?php echo ($cliente_info['genero'] ?? '') === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="feminino" <?php echo ($cliente_info['genero'] ?? '') === 'feminino' ? 'selected' : ''; ?>>Feminino</option>
                                    <option value="outro" <?php echo ($cliente_info['genero'] ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                    <option value="prefiro_nao_informar" <?php echo ($cliente_info['genero'] ?? '') === 'prefiro_nao_informar' ? 'selected' : ''; ?>>Prefiro não informar</option>
                                </select>
                            </div>

                            <!-- Observações Médicas -->
                            <div>
                                <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-notes-medical text-blue-600 mr-2"></i>
                                    Observações Médicas
                                </label>
                                <textarea name="observacoes_medicas" rows="4"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-colors resize-none"
                                    placeholder="Alergias, medicamentos em uso, condições especiais..."><?php echo htmlspecialchars($cliente_info['observacoes_medicas'] ?? ''); ?></textarea>
                                <p class="text-xs text-gray-500 mt-2 ml-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Essas informações são importantes para o seu atendimento
                                </p>
                            </div>

                            <!-- Botões de Ação -->
                            <div class="flex flex-col sm:flex-row justify-end gap-4 pt-6 border-t-2 border-gray-100">
                                <a href="cliente.php" class="bg-gray-500 hover:bg-gray-600 text-white px-8 py-4 rounded-xl font-semibold transition-all text-center shadow-lg hover:-translate-y-0.5">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </a>
                                <button type="submit" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white px-8 py-4 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5">
                                    <i class="fas fa-save mr-2"></i>Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
                
                // Auto-submit quando uma foto é selecionada
                setTimeout(() => {
                    input.form.submit();
                }, 500);
            }
        }
    </script>
</body>
</html>
