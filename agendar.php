<?php
session_start();
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/planos.php';

$username = $_GET['username'] ?? '';
$dentista = Database::find('dentistas', 'username', $username);

if (!$dentista) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Verificar e atualizar automaticamente planos expirados
$dentista = PlanosConfig::verificarEAtualizarPlanosExpirados($dentista);

// Verificar plano atual
$plano_atual = $dentista['plano'] ?? 'teste';
$agendamentos_bloqueados = false;
$mensagem_bloqueio = '';

// Bloquear se o plano está expirado
if ($plano_atual === 'expirado') {
    $agendamentos_bloqueados = true;
    $mensagem_bloqueio = 'O plano desta clínica expirou. Para continuar agendando consultas, é necessário renovar a assinatura.';
}

// Carregar dentistas do consultório
$dentistas_file = 'data/dentistas_consultorio.json';
$dentistas_consultorio = [];
if (file_exists($dentistas_file)) {
    $dentistas_consultorio = json_decode(file_get_contents($dentistas_file), true) ?: [];
}

// Filtrar dentistas ativos do consultório
$dentistas_ativos_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista) {
    return $item['consultorio_id'] === $dentista['id'] && $item['ativo'];
});

// Determinar dentistas disponíveis baseado no plano
$dentistas_disponiveis = [];
$mostrar_dentistas_adicionais = false;

// Plano teste: apenas o consultório (dentista principal)
if ($plano_atual === 'teste') {
    $dentistas_disponiveis[] = [
        'id' => 'principal',
        'nome' => $dentista['nome'],
        'especialidade' => $dentista['especialidade'] ?? 'clinico_geral',
        'foto' => $dentista['foto'] ?? '',
        'cro' => $dentista['cro'] ?? 'Principal',
        'tipo' => 'principal'
    ];
} else {
    // Planos pagos (mensal, semestral, anual)
    // Se tiver dentistas configurados, mostrar eles
    if (count($dentistas_ativos_consultorio) > 0) {
        $mostrar_dentistas_adicionais = true;
        
        // Adicionar dentista principal
        $dentistas_disponiveis[] = [
            'id' => 'principal',
            'nome' => $dentista['nome'],
            'especialidade' => $dentista['especialidade'] ?? 'clinico_geral',
            'foto' => $dentista['foto'] ?? '',
            'cro' => $dentista['cro'] ?? 'Principal',
            'tipo' => 'principal'
        ];
        
        // Adicionar dentistas do consultório
        foreach ($dentistas_ativos_consultorio as $dentista_consultorio) {
            $dentistas_disponiveis[] = [
                'id' => $dentista_consultorio['id'],
                'nome' => $dentista_consultorio['nome'],
                'especialidade' => $dentista_consultorio['especialidade'] ?? 'clinico_geral',
                'foto' => $dentista_consultorio['foto'] ?? '',
                'cro' => $dentista_consultorio['cro'] ?? '',
                'tipo' => 'consultorio'
            ];
        }
    } else {
        // Se não tiver dentistas configurados, mostrar apenas o principal
        $dentistas_disponiveis[] = [
            'id' => 'principal',
            'nome' => $dentista['nome'],
            'especialidade' => $dentista['especialidade'] ?? 'clinico_geral',
            'foto' => $dentista['foto'] ?? '',
            'cro' => $dentista['cro'] ?? 'Principal',
            'tipo' => 'principal'
        ];
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se os agendamentos estão bloqueados
    if ($agendamentos_bloqueados) {
        $error = $mensagem_bloqueio;
    } else {
        $data_agendamento = $_POST['data'] . ' ' . $_POST['hora'];
        $dentista_escolhido = $_POST['dentista_escolhido'] ?? 'principal';
        
        // VERIFICAR SE O HORÁRIO JÁ ESTÁ OCUPADO
        $todos_agendamentos = Database::get('agendamentos');
        
        $horario_ocupado = false;
        foreach ($todos_agendamentos as $ag) {
            // Verificar se é o mesmo dentista, mesma escolha e não está cancelado
            if ($ag['dentista_id'] === $dentista['id'] && 
                $ag['dentista_escolhido'] === $dentista_escolhido && 
                $ag['status'] !== 'cancelado' &&
                $ag['data'] === $data_agendamento) {
                $horario_ocupado = true;
                break;
            }
        }
        
        if ($horario_ocupado) {
            $error = 'Este horário já está ocupado. Por favor, escolha outro horário disponível.';
    } else {
        $data = [
            'dentista_id' => $dentista['id'],
                'dentista_escolhido' => $dentista_escolhido,
            'paciente_nome' => $_POST['paciente_nome'],
            'paciente_email' => $_POST['paciente_email'],
            'paciente_telefone' => $_POST['paciente_telefone'],
            'servico' => $_POST['servico'],
                'data' => $data_agendamento,
            'observacoes' => $_POST['observacoes'] ?? '',
            'status' => 'pendente',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = Database::insert('agendamentos', $data);
        
        if ($result) {
            header('Location: confirmar.php?id=' . $result['id']);
            exit;
        } else {
            $error = 'Erro ao realizar agendamento. Tente novamente.';
        }
        }
    }
}

// Gerar horários disponíveis
function gerarHorarios($horarios_config) {
    $horarios = [];
    $dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    
    for ($i = 0; $i < 30; $i++) {
        $data = date('Y-m-d', strtotime("+$i days"));
        $dia_semana_pt = $dias_semana[date('N', strtotime($data)) - 1];
        
        if (isset($horarios_config[$dia_semana_pt]) && $horarios_config[$dia_semana_pt]['ativo']) {
            $inicio = $horarios_config[$dia_semana_pt]['inicio'];
            $fim = $horarios_config[$dia_semana_pt]['fim'];
            
            $horarios_dia = [];
            $hora_atual = strtotime($inicio);
            $hora_fim = strtotime($fim);
            
            while ($hora_atual < $hora_fim) {
                $horarios_dia[] = date('H:i', $hora_atual);
                $hora_atual = strtotime('+30 minutes', $hora_atual);
            }
            
            if (!empty($horarios_dia)) {
                $horarios[$data] = $horarios_dia;
            }
        }
    }
    
    return $horarios;
}

$horarios_disponiveis = gerarHorarios($dentista['horarios']);

// Especialidades traduzidas
$especialidades_map = [
    'clinico_geral' => 'Clínico Geral',
    'ortodontia' => 'Ortodontia',
    'implantodontia' => 'Implantodontia',
    'endodontia' => 'Endodontia',
    'periodontia' => 'Periodontia',
    'odontopediatria' => 'Odontopediatria',
    'protese' => 'Prótese',
    'cirurgia' => 'Cirurgia',
    'estetica' => 'Odontologia Estética',
    'outros' => 'Outros'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Consulta - <?php echo htmlspecialchars($dentista['clinica']); ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .step-circle {
            transition: all 0.3s ease;
        }
        .step-active {
            transform: scale(1.1);
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
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
<body class="bg-gradient-to-br from-blue-50 via-cyan-50 to-purple-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header da Clínica - Design Melhorado -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8">
            <!-- Banner com Gradiente -->
            <div class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-cyan-600 h-32 sm:h-40">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <!-- Conteúdo do Header -->
            <div class="relative px-6 sm:px-8 pb-8">
                <!-- Foto do Dentista com Badge -->
                <div class="flex justify-center -mt-16 sm:-mt-20 mb-6">
                    <div class="relative">
                        <img src="<?php echo Upload::getImageUrl($dentista['foto'] ?? '', 'dentista', $dentista['nome']); ?>" 
                             alt="Foto do dentista" 
                             class="w-32 h-32 sm:w-40 sm:h-40 rounded-full object-cover border-4 border-white shadow-2xl">
                        <div class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center border-4 border-white shadow-lg">
                            <i class="fas fa-check text-lg sm:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Informações da Clínica -->
                <div class="text-left max-w-3xl mx-auto space-y-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($dentista['clinica']); ?>
                        </h1>
                        
                        <?php if (!empty($dentista['especialidade'])): ?>
                            <div class="inline-flex items-center bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold">
                                <i class="fas fa-stethoscope mr-2"></i>
                                <?php echo $especialidades_map[$dentista['especialidade']] ?? $dentista['especialidade']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                            <span><?php echo htmlspecialchars($dentista['endereco']); ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <?php if (!empty($dentista['telefone'])): ?>
                            <a href="<?php echo Upload::getWhatsAppLink($dentista['telefone']); ?>" 
                               target="_blank"
                               class="flex items-center text-green-600 hover:text-green-700 font-medium transition-colors">
                                <i class="fab fa-whatsapp text-xl mr-2"></i>
                                <span><?php echo htmlspecialchars($dentista['telefone']); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Acesso aos Agendamentos -->
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-4 border border-blue-200 mt-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <p class="text-sm text-blue-800 font-medium flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                Já fez um agendamento? Acesse seus agendamentos aqui:
                            </p>
                            <a href="cliente.php" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md hover:shadow-lg whitespace-nowrap">
                                <i class="fas fa-calendar-check mr-2"></i>
                                Meus Agendamentos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso do Agendamento -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <div class="flex items-center justify-between mb-8">
                <!-- Step 1 -->
                <div class="flex flex-col items-center flex-1">
                    <div id="step-circle-1" class="step-circle step-active w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold mb-2 shadow-lg">
                        1
                    </div>
                    <span class="text-xs text-center font-medium text-gray-600">Dados Pessoais</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2" id="line-1"></div>
                
                <!-- Step 2 -->
                <div class="flex flex-col items-center flex-1">
                    <div id="step-circle-2" class="step-circle w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold mb-2">
                        2
                    </div>
                    <span class="text-xs text-center font-medium text-gray-600">Dentista</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2" id="line-2"></div>
                
                <!-- Step 3 -->
                <div class="flex flex-col items-center flex-1">
                    <div id="step-circle-3" class="step-circle w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold mb-2">
                        3
                    </div>
                    <span class="text-xs text-center font-medium text-gray-600">Serviço</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2" id="line-3"></div>
                
                <!-- Step 4 -->
                <div class="flex flex-col items-center flex-1">
                    <div id="step-circle-4" class="step-circle w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold mb-2">
                        4
                    </div>
                    <span class="text-xs text-center font-medium text-gray-600">Data</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2" id="line-4"></div>
                
                <!-- Step 5 -->
                <div class="flex flex-col items-center flex-1">
                    <div id="step-circle-5" class="step-circle w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold mb-2">
                        5
                    </div>
                    <span class="text-xs text-center font-medium text-gray-600">Horário</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2" id="line-5"></div>
                
                <!-- Step 6 -->
                <div class="flex flex-col items-center flex-1">
                    <div id="step-circle-6" class="step-circle w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold mb-2">
                        6
                    </div>
                    <span class="text-xs text-center font-medium text-gray-600">Confirmação</span>
                </div>
            </div>

            <?php if ($agendamentos_bloqueados): ?>
                <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-2xl p-8 mb-6 shadow-2xl">
                    <div class="flex items-start">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-lock text-white text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold mb-3">Agendamentos Temporariamente Indisponíveis</h3>
                            <p class="text-lg mb-4 opacity-90"><?php echo $mensagem_bloqueio; ?></p>
                            <div class="bg-white bg-opacity-10 rounded-lg p-4 backdrop-blur-sm">
                                <p class="text-sm font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Informações:</p>
                                <ul class="text-sm space-y-1 opacity-90">
                                    <li>• Entre em contato com a clínica para mais informações</li>
                                    <li>• A clínica poderá reativar os agendamentos a qualquer momento</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error && !$agendamentos_bloqueados): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="booking-form" <?php echo $agendamentos_bloqueados ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                <!-- Step 1: Dados Pessoais -->
                <div id="step-1" class="step-content fade-in">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                        <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                        Seus Dados Pessoais
                    </h2>
                    <div class="space-y-6 max-w-2xl mx-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-gray-400"></i>Nome Completo *
                        </label>
                            <input type="text" id="paciente_nome" name="paciente_nome" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Digite seu nome completo">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-gray-400"></i>Email *
                        </label>
                            <input type="email" id="paciente_email" name="paciente_email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="seu@email.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2 text-gray-400"></i>Telefone *
                    </label>
                            <input type="tel" id="paciente_telefone" name="paciente_telefone" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="(00) 00000-0000">
                        </div>

                        <button type="button" onclick="nextStep(2)" 
                            class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-3 px-4 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Escolha do Dentista -->
                <div id="step-2" class="step-content hidden fade-in">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                        <i class="fas fa-user-md mr-2 text-blue-600"></i>
                        <?php echo count($dentistas_disponiveis) > 1 ? 'Escolha o Dentista' : 'Profissional Responsável'; ?>
                    </h2>
                    
                    <?php if ($plano_atual === 'teste' && count($dentistas_disponiveis) === 1): ?>
                        <div class="max-w-2xl mx-auto mb-6">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Plano Teste:</strong> No plano teste, você tem acesso apenas ao dentista principal do consultório. 
                                Faça upgrade para um plano mensal, semestral ou anual para adicionar dentistas à sua equipe.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-6 max-w-3xl mx-auto">
                        <?php if (count($dentistas_disponiveis) > 1): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($dentistas_disponiveis as $index => $dentista_opcao): ?>
                                    <label class="relative cursor-pointer">
                                <input type="radio" name="dentista_escolhido" value="<?php echo $dentista_opcao['id']; ?>" 
                                    <?php echo ($index === 0) ? 'checked' : ''; ?>
                                            class="peer sr-only">
                                        <div class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                            <div class="flex items-center space-x-4">
                                                <img src="<?php echo Upload::getImageUrl($dentista_opcao['foto'] ?? '', $dentista_opcao['tipo'] === 'principal' ? 'dentista' : 'dentista_consultorio', $dentista_opcao['nome']); ?>" 
                                         alt="Foto do dentista" 
                                                     class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 shadow-md">
                                                <div class="flex-1">
                                                    <p class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($dentista_opcao['nome']); ?>
                                                        <?php if ($dentista_opcao['tipo'] === 'principal'): ?>
                                                            <span class="inline-block ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Principal</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <?php echo $especialidades_map[$dentista_opcao['especialidade']] ?? $dentista_opcao['especialidade']; ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        CRO: <?php echo htmlspecialchars($dentista_opcao['cro']); ?>
                                                    </p>
                                                </div>
                                                <div class="text-blue-600 peer-checked:block hidden">
                                                    <i class="fas fa-check-circle text-2xl"></i>
                                                </div>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <?php $dentista_opcao = $dentistas_disponiveis[0]; ?>
                            <input type="hidden" name="dentista_escolhido" value="<?php echo $dentista_opcao['id']; ?>">
                            <div class="max-w-md mx-auto">
                                <div class="p-6 border-2 border-blue-200 rounded-xl bg-blue-50">
                                    <div class="flex items-center space-x-4">
                                        <img src="<?php echo Upload::getImageUrl($dentista_opcao['foto'] ?? '', 'dentista', $dentista_opcao['nome']); ?>" 
                                         alt="Foto do dentista" 
                                             class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg">
                                        <div class="flex-1">
                                            <p class="text-xl font-bold text-gray-900">
                                            <?php echo htmlspecialchars($dentista_opcao['nome']); ?>
                                        </p>
                                            <p class="text-sm text-gray-600">
                                                <?php echo $especialidades_map[$dentista_opcao['especialidade']] ?? $dentista_opcao['especialidade']; ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                CRO: <?php echo htmlspecialchars($dentista_opcao['cro']); ?>
                                            </p>
                                        </div>
                                    </div>
                    </div>
                </div>
                <?php endif; ?>

                        <div class="flex gap-4">
                            <button type="button" onclick="previousStep(1)" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-semibold transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" onclick="nextStep(3)" 
                                class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-3 px-4 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl">
                                Próximo <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Escolha do Serviço -->
                <div id="step-3" class="step-content hidden fade-in">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                        <i class="fas fa-tooth mr-2 text-blue-600"></i>
                        Escolha o Serviço
                    </h2>
                    <div class="space-y-6 max-w-3xl mx-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="servicos-container">
                            <?php 
                            $primeiro_servico_ativo = true;
                            foreach ($dentista['servicos'] as $index => $servico): 
                            ?>
                            <?php if ($servico['ativo']): ?>
                                    <label class="relative cursor-pointer servico-item" 
                                           data-dentistas='<?php echo isset($servico['dentistas']) && !empty($servico['dentistas']) ? json_encode($servico['dentistas']) : '[]'; ?>'>
                                        <input type="radio" name="servico" value="<?php echo htmlspecialchars($servico['nome']); ?>" 
                                            <?php echo ($primeiro_servico_ativo) ? 'checked' : ''; ?>
                                            class="peer sr-only" required>
                                        <div class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-lg font-semibold text-gray-900 mb-2">
                                    <?php echo htmlspecialchars($servico['nome']); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php echo $servico['duracao']; ?> minutos
                                                    </p>
                                                </div>
                                                <div class="text-blue-600 peer-checked:block hidden">
                                                    <i class="fas fa-check-circle text-2xl"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                            <?php 
                            $primeiro_servico_ativo = false;
                            endif; 
                            ?>
                        <?php endforeach; ?>
                        </div>
                        
                        <div id="no-servicos-message" class="hidden col-span-full text-center py-8">
                            <i class="fas fa-info-circle text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600">Nenhum serviço disponível para o dentista selecionado.</p>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" onclick="previousStep(2)" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-semibold transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" onclick="nextStep(4)" 
                                class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-3 px-4 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl">
                                Próximo <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Escolha da Data -->
                <div id="step-4" class="step-content hidden fade-in">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                        Escolha a Data
                    </h2>
                    <div class="space-y-6 max-w-3xl mx-auto">
                        <div class="bg-gray-50 rounded-xl p-6">
                            <!-- Cabeçalho do calendário com navegação -->
                            <div class="flex items-center justify-between mb-4">
                                <button type="button" onclick="previousMonth()" 
                                    class="p-2 rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-chevron-left text-gray-600"></i>
                                </button>
                                <h3 id="calendar-month-year" class="text-lg font-bold text-gray-900"></h3>
                                <button type="button" onclick="nextMonth()" 
                                    class="p-2 rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-chevron-right text-gray-600"></i>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-7 gap-2 text-center text-sm font-medium text-gray-500 mb-4">
                                <div>Dom</div>
                                <div>Seg</div>
                                <div>Ter</div>
                                <div>Qua</div>
                                <div>Qui</div>
                                <div>Sex</div>
                                <div>Sáb</div>
                            </div>
                            <div id="calendar-grid" class="grid grid-cols-7 gap-2">
                                <!-- Dias serão gerados via JavaScript -->
                            </div>
                        </div>
                        
                        <input type="hidden" name="data" id="selected-date" required>
                        <div id="selected-date-display" class="text-center text-lg font-medium text-gray-700 hidden">
                            <i class="fas fa-check-circle mr-2 text-green-500"></i>
                            <span id="date-text"></span>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" onclick="previousStep(3)" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-semibold transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" id="btn-next-step-4" onclick="nextStep(5)" disabled
                                class="flex-1 bg-gray-300 text-gray-500 py-3 px-4 rounded-lg font-semibold cursor-not-allowed">
                                Próximo <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                        </div>
                    </div>

                <!-- Step 5: Escolha do Horário -->
                <div id="step-5" class="step-content hidden fade-in">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                        <i class="fas fa-clock mr-2 text-blue-600"></i>
                        Escolha o Horário
                    </h2>
                    <div class="space-y-6 max-w-3xl mx-auto">
                        <!-- INFO DENTISTA SELECIONADO -->
                        <div id="dentista-selecionado-info" class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-xl p-4 mb-4 shadow-md">
                            <p class="text-sm font-semibold text-purple-900 flex items-center">
                                <i class="fas fa-user-md mr-2 text-lg"></i>
                                <span id="dentista-selecionado-nome"></span>
                            </p>
                        </div>
                        
                        <!-- Info da fonte dos horários -->
                        <div id="fonte-horarios-info" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-blue-800 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span id="fonte-horarios-text"></span>
                            </p>
                        </div>
                        
                        <!-- Legenda -->
                        <div class="flex items-center justify-center gap-6 text-sm mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 border-2 border-gray-300 rounded bg-white"></div>
                                <span class="text-gray-600">Disponível</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 border-2 border-red-200 rounded bg-red-50"></div>
                                <span class="text-gray-600">Ocupado</span>
                        </div>
                    </div>

                        <div id="horarios-container" class="hidden">
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-3" id="horarios-grid">
                                <!-- Horários serão gerados via JavaScript -->
                            </div>
                        </div>
                        
                        <input type="hidden" name="hora" id="selected-time" required>
                        <div id="selected-time-display" class="text-center text-lg font-medium text-gray-700 hidden">
                            <i class="fas fa-check-circle mr-2 text-green-500"></i>
                            <span id="time-text"></span>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" onclick="previousStep(4)" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-semibold transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" id="btn-next-step-5" onclick="nextStep(6)" disabled
                                class="flex-1 bg-gray-300 text-gray-500 py-3 px-4 rounded-lg font-semibold cursor-not-allowed">
                                Próximo <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Confirmação -->
                <div id="step-6" class="step-content hidden fade-in">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                        <i class="fas fa-check-circle mr-2 text-blue-600"></i>
                        Revisão e Confirmação
                    </h2>
                    <div class="space-y-6 max-w-2xl mx-auto">
                        <!-- Resumo do Agendamento -->
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-6 border border-blue-100">
                            <h3 class="font-bold text-lg text-gray-900 mb-4">Resumo do Agendamento</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nome:</span>
                                    <span class="font-medium text-gray-900" id="summary-nome"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium text-gray-900" id="summary-email"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Telefone:</span>
                                    <span class="font-medium text-gray-900" id="summary-telefone"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Dentista:</span>
                                    <span class="font-medium text-gray-900" id="summary-dentista"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Serviço:</span>
                                    <span class="font-medium text-gray-900" id="summary-servico"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Data:</span>
                                    <span class="font-medium text-gray-900" id="summary-data"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Horário:</span>
                                    <span class="font-medium text-gray-900" id="summary-horario"></span>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-comment mr-2 text-gray-400"></i>Observações (opcional)
                    </label>
                    <textarea name="observacoes" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Alguma observação sobre a consulta..."></textarea>
                </div>

                        <div class="flex gap-4">
                            <button type="button" onclick="previousStep(5)" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-semibold transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white py-3 px-4 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl">
                                <i class="fas fa-calendar-check mr-2"></i> Confirmar Agendamento
                </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const horariosDisponiveis = <?php echo json_encode($horarios_disponiveis); ?>;
        const dentistasInfo = <?php echo json_encode($dentistas_disponiveis); ?>;
        const especialidadesMap = <?php echo json_encode($especialidades_map); ?>;
        
        let currentStep = 1;
        let selectedDate = null;
        let selectedTime = null;
        
        // Variáveis para controle do calendário
        let currentCalendarMonth = new Date().getMonth();
        let currentCalendarYear = new Date().getFullYear();
        
        const mesesPt = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];
        
        // Função para ir para o próximo passo
        function nextStep(step) {
            // Validar campos do passo atual antes de avançar
            if (!validateCurrentStep()) {
                return;
            }

            // Ocultar passo atual
            document.getElementById(`step-${currentStep}`).classList.add('hidden');
            
            // Atualizar círculo do passo atual
            document.getElementById(`step-circle-${currentStep}`).classList.remove('step-active', 'bg-blue-600', 'text-white');
            document.getElementById(`step-circle-${currentStep}`).classList.add('bg-green-500', 'text-white');
            document.getElementById(`step-circle-${currentStep}`).innerHTML = '<i class="fas fa-check"></i>';
            
            // Atualizar linha
            if (currentStep < 6) {
                document.getElementById(`line-${currentStep}`).classList.remove('bg-gray-200');
                document.getElementById(`line-${currentStep}`).classList.add('bg-green-500');
            }
            
            // Mostrar próximo passo
            currentStep = step;
            document.getElementById(`step-${step}`).classList.remove('hidden');
            
            // Atualizar círculo do próximo passo
            document.getElementById(`step-circle-${step}`).classList.add('step-active', 'bg-blue-600', 'text-white');
            document.getElementById(`step-circle-${step}`).classList.remove('bg-gray-200', 'text-gray-500');
            
            // Se for o passo 2, adicionar listeners aos dentistas
            if (step === 2) {
                setTimeout(() => {
                    document.querySelectorAll('input[name="dentista_escolhido"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            reloadTimesIfDateSelected();
                            filterServicosByDentista(); // Filtrar serviços também
                        });
                    });
                }, 100);
            }
            
            // Se for o passo 3, filtrar serviços baseado no dentista escolhido
            if (step === 3) {
                filterServicosByDentista();
            }
            
            // Se for o passo 5, mostrar horários
            if (step === 5 && selectedDate) {
                showAvailableTimes(selectedDate);
            }
            
            // Se for o passo 6, atualizar resumo
            if (step === 6) {
                updateSummary();
            }
            
            // Scroll para o topo
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Função para voltar ao passo anterior
        function previousStep(step) {
            // Ocultar passo atual
            document.getElementById(`step-${currentStep}`).classList.add('hidden');
            
            // Atualizar círculo do passo atual
            document.getElementById(`step-circle-${currentStep}`).classList.remove('step-active', 'bg-blue-600', 'bg-green-500');
            document.getElementById(`step-circle-${currentStep}`).classList.add('bg-gray-200', 'text-gray-500');
            document.getElementById(`step-circle-${currentStep}`).textContent = currentStep;
            
            // Atualizar linha anterior
            if (step < 6) {
                document.getElementById(`line-${step}`).classList.remove('bg-green-500');
                document.getElementById(`line-${step}`).classList.add('bg-gray-200');
            }
            
            // Mostrar passo anterior
            currentStep = step;
            document.getElementById(`step-${step}`).classList.remove('hidden');
            
            // Atualizar círculo do passo anterior
            document.getElementById(`step-circle-${step}`).classList.add('step-active', 'bg-blue-600', 'text-white');
            document.getElementById(`step-circle-${step}`).classList.remove('bg-gray-200', 'text-gray-500', 'bg-green-500');
            document.getElementById(`step-circle-${step}`).textContent = step;
            
            // Se voltar ao passo 2, adicionar listeners aos dentistas
            if (step === 2) {
                setTimeout(() => {
                    document.querySelectorAll('input[name="dentista_escolhido"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            reloadTimesIfDateSelected();
                            filterServicosByDentista(); // Filtrar serviços também
                        });
                    });
                }, 100);
            }
            
            // Scroll para o topo
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Validar passo atual
        function validateCurrentStep() {
            if (currentStep === 1) {
                const nome = document.getElementById('paciente_nome').value.trim();
                const email = document.getElementById('paciente_email').value.trim();
                const telefone = document.getElementById('paciente_telefone').value.trim();
                
                if (!nome || !email || !telefone) {
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }
                
                // Validar email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Por favor, insira um email válido.');
                    return false;
                }
            }
            
            return true;
        }

        // Navegar para o mês anterior
        function previousMonth() {
            currentCalendarMonth--;
            if (currentCalendarMonth < 0) {
                currentCalendarMonth = 11;
                currentCalendarYear--;
            }
            generateCalendar();
        }
        
        // Navegar para o próximo mês
        function nextMonth() {
            currentCalendarMonth++;
            if (currentCalendarMonth > 11) {
                currentCalendarMonth = 0;
                currentCalendarYear++;
            }
            generateCalendar();
        }
        
        // Gerar calendário
        function generateCalendar() {
            const calendarGrid = document.getElementById('calendar-grid');
            const monthYearDisplay = document.getElementById('calendar-month-year');
            const today = new Date();
            
            // Atualizar display do mês/ano
            monthYearDisplay.textContent = `${mesesPt[currentCalendarMonth]} ${currentCalendarYear}`;
            
            const firstDay = new Date(currentCalendarYear, currentCalendarMonth, 1);
            const lastDay = new Date(currentCalendarYear, currentCalendarMonth + 1, 0);
            const daysInMonth = lastDay.getDate();
            
            calendarGrid.innerHTML = '';
            
            // Adicionar dias vazios
            for (let i = 0; i < firstDay.getDay(); i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'h-12';
                calendarGrid.appendChild(emptyDay);
            }
            
            // Adicionar dias do mês
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                const dateStr = `${currentCalendarYear}-${String(currentCalendarMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isAvailable = horariosDisponiveis.hasOwnProperty(dateStr);
                const isToday = day === today.getDate() && currentCalendarMonth === today.getMonth() && currentCalendarYear === today.getFullYear();
                const isPast = new Date(dateStr) < new Date(today.toDateString());
                
                dayElement.className = `h-12 flex items-center justify-center text-sm rounded-lg cursor-pointer transition-all font-medium ${
                    isPast ? 'text-gray-300 cursor-not-allowed' :
                    isAvailable ? 'bg-blue-100 text-blue-700 hover:bg-blue-200 hover:shadow-md' :
                    'text-gray-400 cursor-not-allowed'
                }`;
                
                if (isToday) {
                    dayElement.classList.add('ring-2', 'ring-blue-400');
                }
                
                dayElement.textContent = day;
                
                if (isAvailable && !isPast) {
                    dayElement.addEventListener('click', () => selectDate(dateStr, dayElement));
                }
                
                calendarGrid.appendChild(dayElement);
            }
        }
        
        // Selecionar data
        function selectDate(dateStr, element) {
            // Remover seleção anterior
            document.querySelectorAll('#calendar-grid > div').forEach(el => {
                el.classList.remove('bg-blue-600', 'text-white', 'shadow-lg');
            });
            
            // Marcar como selecionado
            element.classList.remove('bg-blue-100', 'text-blue-700');
            element.classList.add('bg-blue-600', 'text-white', 'shadow-lg');
            
            selectedDate = dateStr;
            document.getElementById('selected-date').value = dateStr;
            
            // Mostrar data selecionada
            const dateDisplay = document.getElementById('selected-date-display');
            const dateText = document.getElementById('date-text');
            const date = new Date(dateStr + 'T00:00:00');
            dateText.textContent = date.toLocaleDateString('pt-BR', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            dateDisplay.classList.remove('hidden');
            
            // Habilitar botão próximo
            const btnNext = document.getElementById('btn-next-step-4');
            btnNext.disabled = false;
            btnNext.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btnNext.classList.add('bg-gradient-to-r', 'from-blue-600', 'to-cyan-600', 'hover:from-blue-700', 'hover:to-cyan-700', 'text-white', 'shadow-lg', 'hover:shadow-xl');
        }

        // Mostrar horários disponíveis
        async function showAvailableTimes(dateStr) {
            const horariosContainer = document.getElementById('horarios-container');
            const horariosGrid = document.getElementById('horarios-grid');
            
            if (dateStr) {
                horariosGrid.innerHTML = '<div class="col-span-full text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Verificando disponibilidade...</div>';
                horariosContainer.classList.remove('hidden');
                
                // Buscar horários disponíveis do dentista específico
                const consultorioId = '<?php echo $dentista['id']; ?>';
                const dentistaEscolhido = document.querySelector('input[name="dentista_escolhido"]:checked')?.value || 'principal';
                
                console.log('=== Buscando horários ===');
                console.log('Consultório ID:', consultorioId);
                console.log('Dentista escolhido:', dentistaEscolhido);
                console.log('Data:', dateStr);
                
                // ALERT PARA DEBUG - REMOVER DEPOIS
                if (dentistaEscolhido !== 'principal') {
                    console.log('🟢 USANDO DENTISTA ADICIONAL:', dentistaEscolhido);
                } else {
                    console.log('🔵 USANDO DENTISTA PRINCIPAL');
                }
                
                try {
                    const url = `api/get_dentista_available_times.php?consultorio_id=${consultorioId}&dentista_escolhido=${dentistaEscolhido}&data=${dateStr}`;
                    console.log('URL da API:', url);
                    const response = await fetch(url);
                    const data = await response.json();
                    console.log('Resposta da API:', data);
                    
                    if (data.success) {
                        const horariosDisponiveis = data.horarios_disponiveis || [];
                        console.log('Horários disponíveis:', horariosDisponiveis);
                        console.log('Configuração:', {
                            dia_semana: data.dia_semana,
                            horario_inicio: data.horario_inicio,
                            horario_fim: data.horario_fim,
                            fonte_horarios: data.fonte_horarios
                        });
                        console.log('🕐 FONTE DOS HORÁRIOS:', data.fonte_horarios);
                        
                        // Mostrar dentista selecionado
                        const dentistaSelecionadoInfo = document.getElementById('dentista-selecionado-info');
                        const dentistaSelecionadoNome = document.getElementById('dentista-selecionado-nome');
                        const dentistaSelecionado = dentistasInfo.find(d => d.id === dentistaEscolhido);
                        if (dentistaSelecionado) {
                            dentistaSelecionadoNome.textContent = `Dentista: ${dentistaSelecionado.nome}`;
                        }
                        
                        // Mostrar informação da fonte dos horários
                        const fonteInfo = document.getElementById('fonte-horarios-info');
                        const fonteText = document.getElementById('fonte-horarios-text');
                        if (data.fonte_horarios) {
                            let mensagem = '';
                            if (data.fonte_horarios === 'consultorio_principal') {
                                mensagem = 'Horários do consultório principal';
                            } else if (data.fonte_horarios.startsWith('dentista_')) {
                                const nomeDentista = data.fonte_horarios.replace('dentista_', '');
                                mensagem = `Horários específicos de ${nomeDentista}`;
                            } else if (data.fonte_horarios.includes('fallback')) {
                                mensagem = 'Horários do consultório (dentista não tem horários configurados)';
                            }
                            
                            if (mensagem) {
                                fonteText.textContent = mensagem;
                                fonteInfo.classList.remove('hidden');
                            }
                        }
                        
                horariosGrid.innerHTML = '';
                
                        if (horariosDisponiveis.length === 0) {
                            horariosGrid.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500"><i class="fas fa-calendar-times text-3xl mb-2"></i><p>Nenhum horário disponível para esta data</p><p class="text-sm mt-1">' + (data.mensagem || 'Todos os horários estão ocupados') + '</p></div>';
                        } else {
                            horariosDisponiveis.forEach(horario => {
                    const timeButton = document.createElement('button');
                    timeButton.type = 'button';
                                timeButton.className = 'px-4 py-3 border-2 border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-blue-50 hover:border-blue-300 transition-all';
                    timeButton.textContent = horario;
                    timeButton.addEventListener('click', () => selectTime(horario, timeButton));
                    horariosGrid.appendChild(timeButton);
                });
                        }
            } else {
                        horariosGrid.innerHTML = '<div class="col-span-full text-center py-4 text-red-500"><i class="fas fa-exclamation-circle mr-2"></i>Erro ao carregar horários</div>';
                    }
                } catch (error) {
                    console.error('Erro ao buscar horários:', error);
                    horariosGrid.innerHTML = '<div class="col-span-full text-center py-4 text-red-500"><i class="fas fa-exclamation-circle mr-2"></i>Erro ao carregar horários</div>';
                }
            }
        }
        
        // Selecionar horário
        function selectTime(timeStr, element) {
            // Remover seleção anterior
            document.querySelectorAll('#horarios-grid button').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-lg');
                btn.classList.add('bg-white', 'text-gray-700', 'border-gray-200');
            });
            
            // Marcar como selecionado
            element.classList.remove('bg-white', 'text-gray-700', 'border-gray-200');
            element.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-lg');
            
            selectedTime = timeStr;
            document.getElementById('selected-time').value = timeStr;
            
            // Mostrar horário selecionado
            const timeDisplay = document.getElementById('selected-time-display');
            const timeText = document.getElementById('time-text');
            timeText.textContent = timeStr;
            timeDisplay.classList.remove('hidden');
            
            // Habilitar botão próximo
            const btnNext = document.getElementById('btn-next-step-5');
            btnNext.disabled = false;
            btnNext.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btnNext.classList.add('bg-gradient-to-r', 'from-blue-600', 'to-cyan-600', 'hover:from-blue-700', 'hover:to-cyan-700', 'text-white', 'shadow-lg', 'hover:shadow-xl');
        }

        // Atualizar resumo
        function updateSummary() {
            document.getElementById('summary-nome').textContent = document.getElementById('paciente_nome').value;
            document.getElementById('summary-email').textContent = document.getElementById('paciente_email').value;
            document.getElementById('summary-telefone').textContent = document.getElementById('paciente_telefone').value;
            
            // Dentista
            const dentistaId = document.querySelector('input[name="dentista_escolhido"]:checked').value;
            const dentistaSelecionado = dentistasInfo.find(d => d.id === dentistaId);
            document.getElementById('summary-dentista').textContent = dentistaSelecionado ? dentistaSelecionado.nome : '';
            
            // Serviço
            const servicoSelecionado = document.querySelector('input[name="servico"]:checked');
            document.getElementById('summary-servico').textContent = servicoSelecionado ? servicoSelecionado.value : '';
            
            // Data
            const date = new Date(selectedDate + 'T00:00:00');
            document.getElementById('summary-data').textContent = date.toLocaleDateString('pt-BR', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            // Horário
            document.getElementById('summary-horario').textContent = selectedTime;
        }

        // Recarregar horários quando mudar o dentista selecionado
        function reloadTimesIfDateSelected() {
            if (selectedDate) {
                showAvailableTimes(selectedDate);
            }
        }
        
        // Filtrar serviços baseado no dentista escolhido
        function filterServicosByDentista() {
            const dentistaEscolhido = document.querySelector('input[name="dentista_escolhido"]:checked');
            if (!dentistaEscolhido) return;
            
            const dentistaId = dentistaEscolhido.value;
            console.log('Filtrando serviços para dentista:', dentistaId);
            
            const servicosContainer = document.getElementById('servicos-container');
            const noServicosMsg = document.getElementById('no-servicos-message');
            const servicoItems = document.querySelectorAll('.servico-item');
            
            let servicosVisiveis = 0;
            let primeiroServicoVisivel = null;
            
            servicoItems.forEach(item => {
                const dentistasDoServico = JSON.parse(item.dataset.dentistas || '[]');
                console.log('Serviço:', item.querySelector('input').value, 'Dentistas:', dentistasDoServico);
                
                // Se o array de dentistas estiver vazio, o serviço está disponível para todos
                // Se não estiver vazio, verificar se o dentista escolhido está na lista
                const servicoDisponivel = dentistasDoServico.length === 0 || 
                                         dentistasDoServico.includes(dentistaId);
                
                if (servicoDisponivel) {
                    item.style.display = 'block';
                    servicosVisiveis++;
                    if (!primeiroServicoVisivel) {
                        primeiroServicoVisivel = item.querySelector('input[name="servico"]');
                    }
                } else {
                    item.style.display = 'none';
                    // Desmarcar se estiver selecionado
                    const radioInput = item.querySelector('input[name="servico"]');
                    if (radioInput && radioInput.checked) {
                        radioInput.checked = false;
                    }
                }
            });
            
            // Mostrar/ocultar mensagem de "nenhum serviço"
            if (servicosVisiveis === 0) {
                noServicosMsg.classList.remove('hidden');
                servicosContainer.style.display = 'none';
            } else {
                noServicosMsg.classList.add('hidden');
                servicosContainer.style.display = 'grid';
                
                // Selecionar automaticamente o primeiro serviço visível
                if (primeiroServicoVisivel) {
                    primeiroServicoVisivel.checked = true;
                }
            }
            
            console.log('Serviços visíveis:', servicosVisiveis);
        }
        
        // Inicializar calendário quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            generateCalendar();
            
            // Adicionar listeners aos dentistas para filtrar serviços
            document.querySelectorAll('input[name="dentista_escolhido"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    filterServicosByDentista();
                });
            });
        });
    </script>
</body>
</html>
<!-- FIXED v3.0 - 2025-10-26 23:00 -->
