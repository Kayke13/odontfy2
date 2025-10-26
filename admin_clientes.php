<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';
require_once 'config/upload.php';

AdminAuth::requireLogin();

$success = '';
$error = '';

// Buscar todos os agendamentos
$agendamentos = Database::get('agendamentos');
$dentistas = Database::get('dentistas');

// Criar array de dentistas para lookup
$dentistas_lookup = [];
foreach ($dentistas as $dentista) {
    $dentistas_lookup[$dentista['id']] = $dentista;
}

// Agrupar clientes únicos
$clientes_agrupados = [];
foreach ($agendamentos as $agendamento) {
    $chave_cliente = $agendamento['paciente_email'] . '_' . $agendamento['paciente_telefone'];
    
    if (!isset($clientes_agrupados[$chave_cliente])) {
        $clientes_agrupados[$chave_cliente] = [
            'nome' => $agendamento['paciente_nome'],
            'email' => $agendamento['paciente_email'],
            'telefone' => $agendamento['paciente_telefone'],
            'agendamentos' => [],
            'total_agendamentos' => 0,
            'ultimo_agendamento' => null,
            'proximo_agendamento' => null,
            'dentistas' => []
        ];
    }
    
    $clientes_agrupados[$chave_cliente]['agendamentos'][] = $agendamento;
    $clientes_agrupados[$chave_cliente]['total_agendamentos']++;
    
    // Adicionar dentista único
    $dentista_id = $agendamento['dentista_id'];
    if (!in_array($dentista_id, $clientes_agrupados[$chave_cliente]['dentistas'])) {
        $clientes_agrupados[$chave_cliente]['dentistas'][] = $dentista_id;
    }
    
    // Último agendamento
    if (!$clientes_agrupados[$chave_cliente]['ultimo_agendamento'] || 
        strtotime($agendamento['data']) > strtotime($clientes_agrupados[$chave_cliente]['ultimo_agendamento']['data'])) {
        $clientes_agrupados[$chave_cliente]['ultimo_agendamento'] = $agendamento;
    }
    
    // Próximo agendamento (futuro)
    if (strtotime($agendamento['data']) > time()) {
        if (!$clientes_agrupados[$chave_cliente]['proximo_agendamento'] || 
            strtotime($agendamento['data']) < strtotime($clientes_agrupados[$chave_cliente]['proximo_agendamento']['data'])) {
            $clientes_agrupados[$chave_cliente]['proximo_agendamento'] = $agendamento;
        }
    }
}

// Converter para array e ordenar por nome
$clientes = array_values($clientes_agrupados);
usort($clientes, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

// Carregar dados dos clientes (se existir perfil)
$clientes_file = 'data/clientes.json';
$clientes_data = [];
if (file_exists($clientes_file)) {
    $clientes_data = json_decode(file_get_contents($clientes_file), true) ?: [];
}

// Integrar dados dos perfis
foreach ($clientes as &$cliente) {
    $cliente_key = $cliente['email'] . '_' . $cliente['telefone'];
    $cliente_perfil = $clientes_data[$cliente_key] ?? [];
    
    $cliente['foto'] = $cliente_perfil['foto'] ?? '';
    $cliente['data_nascimento'] = $cliente_perfil['data_nascimento'] ?? '';
    $cliente['genero'] = $cliente_perfil['genero'] ?? '';
    $cliente['observacoes_medicas'] = $cliente_perfil['observacoes_medicas'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - Admin Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
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

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .animate-slide-in {
            animation: slideIn 0.6s ease-out forwards;
        }

        /* Card hover effect */
        .modern-card {
            transition: all 0.3s ease;
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50 font-inter antialiased">
        <!-- Header Moderno -->
        <!-- Header Moderno -->
    <header class="bg-white shadow-md border-b-4 border-orange-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciar Clientes</h1>
                        <p class="text-sm text-gray-500">Painel Administrativo</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="admin_dashboard.php" class="flex items-center space-x-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200">
                        <i class="fas fa-arrow-left"></i>
                        <span class="hidden sm:inline font-medium">Dashboard</span>
                    </a>
                    <a href="admin_logout.php" class="flex items-center space-x-2 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline font-medium">Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total de Clientes</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($clientes); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total de Agendamentos</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($agendamentos); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-user-md text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Dentistas Atendidos</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($dentistas); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Média por Cliente</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo count($clientes) > 0 ? round(count($agendamentos) / count($clientes), 1) : 0; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Clientes -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-list mr-2" style="color: #1570EF;"></i>Lista de Clientes
            </h2>

            <?php if (empty($clientes)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum cliente encontrado</h3>
                    <p class="text-gray-500">Os clientes aparecerão aqui quando fizerem agendamentos.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-200">
                                            <img src="<?php echo Upload::getImageUrl($cliente['foto'] ?? '', 'cliente', $cliente['nome']); ?>" 
                                                 alt="Foto do cliente" 
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo htmlspecialchars($cliente['nome']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($cliente['email']); ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($cliente['telefone']); ?>
                                            </p>
                                            <?php if (!empty($cliente['data_nascimento'])): ?>
                                                <p class="text-sm text-gray-500">
                                                    <i class="fas fa-birthday-cake mr-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($cliente['data_nascimento'])); ?>
                                                    <?php if (!empty($cliente['genero'])): ?>
                                                        <span class="mx-2">•</span>
                                                        <?php echo ucfirst($cliente['genero']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <div class="text-2xl font-bold text-blue-600">
                                                <?php echo $cliente['total_agendamentos']; ?>
                                            </div>
                                            <div class="text-sm text-gray-600">Total de Agendamentos</div>
                                        </div>
                                        
                                        <div class="bg-green-50 rounded-lg p-4">
                                            <div class="text-sm font-medium text-green-600">
                                                <?php echo count($cliente['dentistas']); ?> Dentista(s)
                                            </div>
                                            <div class="text-sm text-gray-600">Consultórios Atendidos</div>
                                        </div>
                                        
                                        <?php if ($cliente['ultimo_agendamento']): ?>
                                            <div class="bg-purple-50 rounded-lg p-4">
                                                <div class="text-sm font-medium text-purple-600">
                                                    <?php echo date('d/m/Y', strtotime($cliente['ultimo_agendamento']['data'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-600">Último Agendamento</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($cliente['proximo_agendamento']): ?>
                                        <div class="bg-blue-50 rounded-lg p-4 mb-4">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Próximo Agendamento</h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo date('d/m/Y H:i', strtotime($cliente['proximo_agendamento']['data'])); ?> - 
                                                <?php echo htmlspecialchars($cliente['proximo_agendamento']['servico']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Dr(a). <?php echo htmlspecialchars($dentistas_lookup[$cliente['proximo_agendamento']['dentista_id']]['nome'] ?? 'N/A'); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($cliente['observacoes_medicas'])): ?>
                                        <div class="bg-yellow-50 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Observações Médicas</h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($cliente['observacoes_medicas']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ml-4 flex flex-col space-y-2">
                                    <a href="tel:<?php echo htmlspecialchars($cliente['telefone']); ?>" 
                                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm text-center">
                                        <i class="fas fa-phone mr-2"></i>Ligar
                                    </a>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm text-center">
                                        <i class="fas fa-envelope mr-2"></i>Email
                                    </a>
                                    
                                    <button onclick="verAgendamentos('<?php echo htmlspecialchars($cliente['nome']); ?>', <?php echo json_encode($cliente['agendamentos']); ?>)" 
                                            class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm text-center">
                                        <i class="fas fa-calendar-alt mr-2"></i>Ver Agendamentos
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para ver agendamentos -->
    <div id="modalAgendamentos" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modalTitulo">Agendamentos</h3>
                        <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="modalConteudo">
                        <!-- Conteúdo será inserido via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }

        function verAgendamentos(nomeCliente, agendamentos) {
            document.getElementById('modalTitulo').textContent = 'Agendamentos de ' + nomeCliente;
            
            let conteudo = '<div class="space-y-3">';
            
            if (agendamentos.length === 0) {
                conteudo += '<p class="text-gray-500 text-center py-4">Nenhum agendamento encontrado.</p>';
            } else {
                agendamentos.forEach(function(agendamento) {
                    conteudo += '<div class="border border-gray-200 rounded-lg p-4">';
                    conteudo += '<div class="flex justify-between items-start">';
                    conteudo += '<div>';
                    conteudo += '<h4 class="font-medium text-gray-900">' + agendamento.servico + '</h4>';
                    conteudo += '<p class="text-sm text-gray-600">' + new Date(agendamento.data).toLocaleDateString('pt-BR') + ' às ' + new Date(agendamento.data).toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'}) + '</p>';
                    conteudo += '</div>';
                    conteudo += '<span class="px-2 py-1 text-xs font-medium rounded-full ' + 
                        (agendamento.status === 'pendente' ? 'bg-yellow-100 text-yellow-800' :
                         agendamento.status === 'confirmado' ? 'bg-green-100 text-green-800' :
                         'bg-red-100 text-red-800') + '">';
                    conteudo += agendamento.status.charAt(0).toUpperCase() + agendamento.status.slice(1);
                    conteudo += '</span>';
                    conteudo += '</div>';
                    conteudo += '</div>';
                });
            }
            
            conteudo += '</div>';
            
            document.getElementById('modalConteudo').innerHTML = conteudo;
            document.getElementById('modalAgendamentos').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modalAgendamentos').classList.add('hidden');
        }

        // Fechar modal clicando fora
        document.getElementById('modalAgendamentos').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center">
                <p class="text-gray-600 text-sm">© 2025 Odontfy. Todos os direitos reservados.</p>
                <p class="text-gray-500 text-sm mt-2 sm:mt-0">Dashboard Administrativo v2.0</p>
            </div>
        </div>
    </footer>
</body>
</html>
