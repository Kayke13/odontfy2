<?php
session_start();
require_once 'auth.php';
require_once 'config/upload.php';

$error = '';
$success = '';
$current_step = 1;
$form_data = [];

// Se já tem dados do formulário, manter o passo atual
if (isset($_POST['step'])) {
    $current_step = (int)$_POST['step'];
    $form_data = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'next_step') {
        // Validar dados do passo atual
        $validation_result = validateStep($current_step, $_POST);
        
        if ($validation_result['valid']) {
            $current_step++;
            $form_data = array_merge($form_data, $_POST);
        } else {
            $error = $validation_result['message'];
        }
    } elseif ($action === 'prev_step') {
        $current_step--;
    } elseif ($action === 'submit') {
        // Processar cadastro final
        $data = [
            'nome' => $form_data['nome'],
            'email' => $form_data['email'],
            'username' => $form_data['username'],
            'password' => $form_data['password'],
            'clinica' => $form_data['clinica'],
            'telefone' => $form_data['telefone'],
            'especialidade' => $form_data['especialidade'],
            'endereco' => $form_data['endereco'],
            'cidade' => $form_data['cidade'],
            'estado' => $form_data['estado'],
            'cep' => $form_data['cep']
        ];
        
        $result = Auth::register($data);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

function validateStep($step, $data) {
    switch ($step) {
        case 1:
            if (empty($data['nome']) || empty($data['email']) || empty($data['clinica'])) {
                return ['valid' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.'];
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['valid' => false, 'message' => 'Por favor, insira um email válido.'];
            }
            break;
        case 2:
            if (empty($data['username']) || empty($data['password']) || empty($data['telefone'])) {
                return ['valid' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.'];
            }
            if (strlen($data['password']) < 6) {
                return ['valid' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];
            }
            
            // Validar username
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
                return ['valid' => false, 'message' => 'Username deve conter apenas letras, números, hífens e underscores.'];
            }
            if (strlen($data['username']) < 3) {
                return ['valid' => false, 'message' => 'Username deve ter pelo menos 3 caracteres.'];
            }
            if (strlen($data['username']) > 20) {
                return ['valid' => false, 'message' => 'Username deve ter no máximo 20 caracteres.'];
            }
            
            // Verificar se username já existe
            require_once 'config/database.php';
            if (Database::find('dentistas', 'username', $data['username'])) {
                return ['valid' => false, 'message' => 'Este username já está em uso. Escolha outro.'];
            }
            break;
        case 3:
            if (empty($data['especialidade'])) {
                return ['valid' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.'];
            }
            break;
        case 4:
            if (empty($data['endereco']) || empty($data['cidade']) || empty($data['estado']) || empty($data['cep'])) {
                return ['valid' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.'];
            }
            break;
    }
    return ['valid' => true, 'message' => ''];
}

function getStepTitle($step) {
    switch ($step) {
        case 1: return 'Informações Básicas';
        case 2: return 'Credenciais de Acesso';
        case 3: return 'Dados Profissionais';
        case 4: return 'Localização';
        case 5: return 'Logo do Consultório';
        default: return 'Cadastro';
    }
}

function getStepDescription($step) {
    switch ($step) {
        case 1: return 'Conte-nos sobre você e sua clínica';
        case 2: return 'Crie seu acesso ao sistema';
        case 3: return 'Informe seus dados profissionais';
        case 4: return 'Onde está localizada sua clínica';
        case 5: return 'Adicione a logo do seu consultório';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Odontfy</title>
        <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Fonte Inter em todo o site, EXCETO nos ícones */
        body, h1, h2, h3, h4, h5, h6, p, a, button, input, textarea, select, label, span, div {
            font-family: 'Inter', sans-serif !important;
        }
        
        /* Garantir que os ícones do FontAwesome funcionem */
        .fa, .fas, .far, .fal, .fab, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        i.fa, i.fas, i.far, i.fal, i.fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-50 min-h-screen font-inter">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-4xl font-bold text-blue-600 mb-2">
                    <i class="fas fa-tooth mr-2"></i>Odontfy
                </h1>
                <h2 class="text-2xl font-bold text-gray-900">Criar Conta</h2>
                <p class="mt-2 text-gray-600">
                    Cadastro rápido em 5 passos • Teste grátis de 7 dias
                </p>
            </div>
            
            <!-- Progress Bar -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-500">Passo <?php echo $current_step; ?> de 5</span>
                        <span class="text-sm text-gray-400">•</span>
                        <span class="text-sm text-gray-600"><?php echo getStepTitle($current_step); ?></span>
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php echo round(($current_step / 5) * 100); ?>% concluído
                    </div>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                         style="width: <?php echo ($current_step / 5) * 100; ?>%"></div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex justify-between">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                                <?php echo $i <= $current_step ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                                <?php echo $i; ?>
                            </div>
                            <span class="text-xs text-gray-500 mt-1 text-center">
                                <?php 
                                $titles = ['Básico', 'Acesso', 'Profissional', 'Local', 'Logo'];
                                echo $titles[$i-1]; 
                                ?>
                            </span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Form -->
            <div class="bg-white py-8 px-6 shadow-xl rounded-lg">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900"><?php echo getStepTitle($current_step); ?></h3>
                    <p class="text-gray-600 mt-1"><?php echo getStepDescription($current_step); ?></p>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="step" value="<?php echo $current_step; ?>">
                    
                    <!-- Campos hidden para manter dados entre passos -->
                    <?php if ($current_step > 1): ?>
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($form_data['nome'] ?? ''); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                        <input type="hidden" name="clinica" value="<?php echo htmlspecialchars($form_data['clinica'] ?? ''); ?>">
                    <?php endif; ?>
                    
                    <?php if ($current_step > 2): ?>
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>">
                        <input type="hidden" name="password" value="<?php echo htmlspecialchars($form_data['password'] ?? ''); ?>">
                        <input type="hidden" name="telefone" value="<?php echo htmlspecialchars($form_data['telefone'] ?? ''); ?>">
                    <?php endif; ?>
                    
                    <?php if ($current_step > 3): ?>
                        <input type="hidden" name="especialidade" value="<?php echo htmlspecialchars($form_data['especialidade'] ?? ''); ?>">
                    <?php endif; ?>
                    
                    <?php if ($current_step > 4): ?>
                        <input type="hidden" name="endereco" value="<?php echo htmlspecialchars($form_data['endereco'] ?? ''); ?>">
                        <input type="hidden" name="cidade" value="<?php echo htmlspecialchars($form_data['cidade'] ?? ''); ?>">
                        <input type="hidden" name="estado" value="<?php echo htmlspecialchars($form_data['estado'] ?? ''); ?>">
                        <input type="hidden" name="cep" value="<?php echo htmlspecialchars($form_data['cep'] ?? ''); ?>">
                    <?php endif; ?>
                    
                    <?php if ($current_step === 1): ?>
                        <!-- Passo 1: Informações Básicas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-1"></i>Nome Completo *
                                </label>
                                <input type="text" name="nome" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Dr. João Silva"
                                    value="<?php echo htmlspecialchars($form_data['nome'] ?? ''); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-building mr-1"></i>Nome da Clínica *
                                </label>
                                <input type="text" name="clinica" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Clínica OdontoFy"
                                    value="<?php echo htmlspecialchars($form_data['clinica'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-1"></i>Email *
                            </label>
                            <input type="email" name="email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="seu@email.com"
                                value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                        </div>
                        
                    <?php elseif ($current_step === 2): ?>
                        <!-- Passo 2: Credenciais de Acesso -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-link mr-1"></i>Username (Link Personalizado) *
                            </label>
                            <div class="flex">
                                <span class="inline-flex items-center px-4 py-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm font-medium">
                                    odontfy.com/
                                </span>
                                <input type="text" name="username" required id="username-input"
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="sua-clinica"
                                    value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                                    onkeyup="validateUsername(this.value)">
                            </div>
                            <div id="username-feedback" class="mt-2 text-xs"></div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Seu link será: <strong id="link-preview">odontfy.com/sua-clinica</strong>
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1"></i>Senha *
                                </label>
                                <input type="password" name="password" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="Mínimo 6 caracteres"
                                    value="<?php echo htmlspecialchars($form_data['password'] ?? ''); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-1"></i>Telefone *
                                </label>
                                <input type="tel" name="telefone" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="(11) 99999-9999"
                                    value="<?php echo htmlspecialchars($form_data['telefone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                    <?php elseif ($current_step === 3): ?>
                        <!-- Passo 3: Dados Profissionais -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-stethoscope mr-1"></i>Especialidade *
                            </label>
                            <select name="especialidade" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <option value="">Selecione sua especialidade</option>
                                <option value="clinico_geral" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'clinico_geral') ? 'selected' : ''; ?>>Clínico Geral</option>
                                <option value="ortodontia" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'ortodontia') ? 'selected' : ''; ?>>Ortodontia</option>
                                    <option value="implantodontia" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'implantodontia') ? 'selected' : ''; ?>>Implantodontia</option>
                                    <option value="endodontia" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'endodontia') ? 'selected' : ''; ?>>Endodontia</option>
                                    <option value="periodontia" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'periodontia') ? 'selected' : ''; ?>>Periodontia</option>
                                    <option value="odontopediatria" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'odontopediatria') ? 'selected' : ''; ?>>Odontopediatria</option>
                                    <option value="prostese" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'prostese') ? 'selected' : ''; ?>>Prótese</option>
                                    <option value="cirurgia" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'cirurgia') ? 'selected' : ''; ?>>Cirurgia</option>
                                    <option value="estetica" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'estetica') ? 'selected' : ''; ?>>Odontologia Estética</option>
                                    <option value="outros" <?php echo (isset($form_data['especialidade']) && $form_data['especialidade'] === 'outros') ? 'selected' : ''; ?>>Outros</option>
                                </select>
                            </div>
                        </div>
                        
                    <?php elseif ($current_step === 4): ?>
                        <!-- Passo 4: Localização -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i>Endereço Completo *
                            </label>
                            <textarea name="endereco" required rows="2"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="Rua das Flores, 123, Centro"><?php echo htmlspecialchars($form_data['endereco'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-city mr-1"></i>Cidade *
                                </label>
                                <input type="text" name="cidade" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="São Paulo"
                                    value="<?php echo htmlspecialchars($form_data['cidade'] ?? ''); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map mr-1"></i>Estado *
                                </label>
                                <select name="estado" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <option value="">Selecione</option>
                                    <option value="SP" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'SP') ? 'selected' : ''; ?>>São Paulo</option>
                                    <option value="RJ" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'RJ') ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                    <option value="MG" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'MG') ? 'selected' : ''; ?>>Minas Gerais</option>
                                    <option value="RS" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'RS') ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                    <option value="PR" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'PR') ? 'selected' : ''; ?>>Paraná</option>
                                    <option value="SC" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'SC') ? 'selected' : ''; ?>>Santa Catarina</option>
                                    <option value="BA" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'BA') ? 'selected' : ''; ?>>Bahia</option>
                                    <option value="GO" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'GO') ? 'selected' : ''; ?>>Goiás</option>
                                    <option value="PE" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'PE') ? 'selected' : ''; ?>>Pernambuco</option>
                                    <option value="CE" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'CE') ? 'selected' : ''; ?>>Ceará</option>
                                    <option value="DF" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'DF') ? 'selected' : ''; ?>>Distrito Federal</option>
                                    <option value="ES" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'ES') ? 'selected' : ''; ?>>Espírito Santo</option>
                                    <option value="MT" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'MT') ? 'selected' : ''; ?>>Mato Grosso</option>
                                    <option value="MS" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'MS') ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                    <option value="PA" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'PA') ? 'selected' : ''; ?>>Pará</option>
                                    <option value="PB" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'PB') ? 'selected' : ''; ?>>Paraíba</option>
                                    <option value="AM" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'AM') ? 'selected' : ''; ?>>Amazonas</option>
                                    <option value="RN" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'RN') ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                    <option value="AL" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'AL') ? 'selected' : ''; ?>>Alagoas</option>
                                    <option value="SE" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'SE') ? 'selected' : ''; ?>>Sergipe</option>
                                    <option value="RO" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'RO') ? 'selected' : ''; ?>>Rondônia</option>
                                    <option value="AC" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'AC') ? 'selected' : ''; ?>>Acre</option>
                                    <option value="RR" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'RR') ? 'selected' : ''; ?>>Roraima</option>
                                    <option value="AP" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'AP') ? 'selected' : ''; ?>>Amapá</option>
                                    <option value="TO" <?php echo (isset($form_data['estado']) && $form_data['estado'] === 'TO') ? 'selected' : ''; ?>>Tocantins</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-mail-bulk mr-1"></i>CEP *
                                </label>
                                <input type="text" name="cep" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    placeholder="01234-567"
                                    value="<?php echo htmlspecialchars($form_data['cep'] ?? ''); ?>">
                            </div>
                        </div>
                        
                    <?php elseif ($current_step === 5): ?>
                        <!-- Passo 5: Logo do Consultório -->
                        <div class="text-center">
                            <div class="mb-6">
                                <i class="fas fa-image text-6xl text-blue-200 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Adicione a logo do seu consultório</h3>
                                <p class="text-gray-600">Esta logo será exibida na página de agendamento para seus pacientes</p>
                            </div>
                            
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 hover:border-blue-400 transition duration-200">
                                <input type="file" name="foto_consultorio" id="foto_consultorio" 
                                       accept="image/*" class="hidden" onchange="previewImage(this)">
                                <label for="foto_consultorio" class="cursor-pointer">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                        <p class="text-lg font-medium text-gray-700 mb-2">Clique para fazer upload</p>
                                        <p class="text-sm text-gray-500">PNG, JPG ou JPEG até 5MB</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div id="image-preview" class="mt-4 hidden">
                                <img id="preview-img" class="mx-auto max-w-xs rounded-lg shadow-lg">
                                <p class="text-sm text-gray-600 mt-2">Pré-visualização da imagem</p>
                            </div>
                            
                            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <div class="text-sm text-blue-700">
                                        <strong>Dica:</strong> Use uma logo clara e profissional do seu consultório para transmitir confiança aos pacientes!
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6">
                        <?php if ($current_step > 1): ?>
                            <button type="submit" name="action" value="prev_step"
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                                <i class="fas fa-arrow-left mr-2"></i>Voltar
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        
                        <?php if ($current_step < 5): ?>
                            <button type="submit" name="action" value="next_step"
                                class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition duration-200">
                                Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="submit"
                                class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition duration-200">
                                <i class="fas fa-check mr-2"></i>Finalizar Cadastro
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Teste Grátis Banner -->
                <div class="mt-8 bg-gradient-to-r from-green-100 to-blue-100 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-gift text-green-600 text-2xl mr-3"></i>
                        <div class="text-center">
                            <strong class="text-green-800 text-lg">Teste Grátis de 7 dias</strong>
                            <p class="text-green-700 text-sm">Sem cartão de crédito • Cancele quando quiser</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Já tem uma conta? 
                        <a href="login.php" class="text-blue-600 hover:text-blue-500 font-medium">
                            Faça login aqui
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Validação de username em tempo real
        function validateUsername(username) {
            const feedback = document.getElementById('username-feedback');
            const linkPreview = document.getElementById('link-preview');
            const usernameInput = document.getElementById('username-input');
            
            // Atualizar preview do link
            linkPreview.textContent = 'odontfy.com/' + (username || 'sua-clinica');
            
            if (!username) {
                feedback.innerHTML = '';
                usernameInput.classList.remove('border-red-500', 'border-green-500');
                return;
            }
            
            // Validações básicas
            if (username.length < 3) {
                showFeedback('Username deve ter pelo menos 3 caracteres', 'error');
                return;
            }
            
            if (username.length > 20) {
                showFeedback('Username deve ter no máximo 20 caracteres', 'error');
                return;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                showFeedback('Username deve conter apenas letras, números, hífens e underscores', 'error');
                return;
            }
            
            // Verificar disponibilidade via AJAX
            checkUsernameAvailability(username);
        }
        
        function showFeedback(message, type) {
            const feedback = document.getElementById('username-feedback');
            const usernameInput = document.getElementById('username-input');
            
            if (type === 'error') {
                feedback.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i><span class="text-red-600">' + message + '</span>';
                usernameInput.classList.remove('border-green-500');
                usernameInput.classList.add('border-red-500');
            } else if (type === 'success') {
                feedback.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1"></i><span class="text-green-600">' + message + '</span>';
                usernameInput.classList.remove('border-red-500');
                usernameInput.classList.add('border-green-500');
            } else if (type === 'loading') {
                feedback.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-500 mr-1"></i><span class="text-blue-600">Verificando disponibilidade...</span>';
                usernameInput.classList.remove('border-red-500', 'border-green-500');
            }
        }
        
        function checkUsernameAvailability(username) {
            showFeedback('Verificando disponibilidade...', 'loading');
            
            // Fazer requisição AJAX para verificar disponibilidade
            fetch('check_username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username)
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    showFeedback(data.message, 'success');
                } else {
                    showFeedback(data.message, 'error');
                }
            })
            .catch(error => {
                showFeedback('Erro ao verificar disponibilidade. Tente novamente.', 'error');
            });
        }
        
        // Auto-save form data to localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            // Load saved data
            inputs.forEach(input => {
                const savedValue = localStorage.getItem('register_' + input.name);
                if (savedValue && !input.value) {
                    input.value = savedValue;
                }
            });
            
            // Save data on change
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    localStorage.setItem('register_' + input.name, input.value);
                });
            });
            
            // Clear localStorage on successful submit
            form.addEventListener('submit', function(e) {
                if (e.submitter.value === 'submit') {
                    localStorage.clear();
                }
            });
        });
        
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</body>
</html>
