<?php
session_start();
require_once 'config/database.php';
require_once 'config/upload.php';

$agendamento_id = $_GET['id'] ?? '';
$agendamento = Database::find('agendamentos', 'id', $agendamento_id);

if (!$agendamento) {
    header('Location: index.php');
    exit;
}

$dentista = Database::find('dentistas', 'id', $agendamento['dentista_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Confirmado - Odontfy</title>
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
        
        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(0deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.2) rotate(180deg);
            }
            100% {
                transform: scale(1) rotate(360deg);
                opacity: 1;
            }
        }
        
        .animate-checkmark {
            animation: checkmark 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-cyan-50 to-gray-50 min-h-screen font-inter">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header de Sucesso -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-8 py-12 text-center">
                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl animate-checkmark">
                    <i class="fas fa-check text-green-600 text-5xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-white mb-3 animate-fade-in-up">
                    Agendamento Confirmado!
                </h1>
                <p class="text-xl text-green-50 animate-fade-in-up">
                    Sua consulta foi agendada com sucesso
                </p>
            </div>

            <!-- Detalhes do Agendamento -->
            <div class="p-8">
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-8 mb-6 border-2 border-blue-100">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-calendar-check text-white text-xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Detalhes do Agendamento</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user text-blue-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Paciente</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 ml-8"><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></p>
                        </div>
                        
                        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-hospital text-blue-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Clínica</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 ml-8"><?php echo htmlspecialchars($dentista['clinica']); ?></p>
                        </div>
                        
                        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-md text-blue-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Dentista</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 ml-8"><?php echo htmlspecialchars($dentista['nome']); ?></p>
                        </div>
                        
                        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-tooth text-blue-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Serviço</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 ml-8"><?php echo htmlspecialchars($agendamento['servico']); ?></p>
                        </div>
                        
                        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-calendar-day text-blue-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Data</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 ml-8"><?php echo date('d/m/Y', strtotime($agendamento['data'])); ?></p>
                        </div>
                        
                        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-clock text-blue-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Horário</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 ml-8"><?php echo date('H:i', strtotime($agendamento['data'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6 bg-white rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-yellow-600 mr-3 text-lg"></i>
                                <span class="text-sm text-gray-600 font-semibold">Status do Agendamento</span>
                            </div>
                            <span class="px-4 py-2 text-sm font-bold bg-yellow-100 text-yellow-800 rounded-full uppercase">
                                <?php echo ucfirst($agendamento['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Informações de Contato -->
                <div class="bg-white rounded-2xl p-6 mb-6 border-2 border-gray-200">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-phone text-white"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900">Contato da Clínica</h4>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="<?php echo Upload::getWhatsAppLink($dentista['telefone']); ?>" target="_blank" 
                           class="flex-1 flex items-center justify-center bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-4 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl">
                            <i class="fab fa-whatsapp text-2xl mr-3"></i>
                            <div class="text-left">
                                <p class="text-xs opacity-90">WhatsApp</p>
                                <p class="text-lg"><?php echo htmlspecialchars($dentista['telefone']); ?></p>
                            </div>
                        </a>
                        <div class="flex-1 flex items-center justify-center bg-gradient-to-r from-gray-100 to-gray-200 px-6 py-4 rounded-xl">
                            <i class="fas fa-map-marker-alt text-gray-600 text-xl mr-3"></i>
                            <div class="text-left">
                                <p class="text-xs text-gray-600">Endereço</p>
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($dentista['endereco']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Próximos Passos -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-2xl p-6 mb-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-list-check text-white text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900 mb-4">Próximos Passos</h4>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-blue-600 mr-3 mt-1"></i>
                                    <p class="text-gray-700">Você receberá um email de confirmação em breve</p>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-blue-600 mr-3 mt-1"></i>
                                    <p class="text-gray-700">Um lembrete será enviado 24h antes da consulta</p>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-blue-600 mr-3 mt-1"></i>
                                    <p class="text-gray-700">Em caso de dúvidas, entre em contato com a clínica</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aviso Importante -->
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-2xl p-6 mb-8">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900 mb-2">Importante</h4>
                            <p class="text-gray-700">
                                Este agendamento está <strong>pendente de confirmação</strong> pela clínica. 
                                Você será notificado assim que for confirmado.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="agendar.php?username=<?php echo $dentista['username']; ?>" 
                       class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white px-8 py-4 rounded-xl font-bold text-lg text-center transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-calendar-plus mr-2"></i>Agendar Outra Consulta
                    </a>
                    
                    <a href="index.php" 
                       class="flex-1 bg-white border-2 border-gray-300 hover:border-gray-400 text-gray-700 hover:text-gray-900 px-8 py-4 rounded-xl font-bold text-lg text-center transition-all">
                        <i class="fas fa-home mr-2"></i>Voltar ao Início
                    </a>
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
    </script>
</body>
</html>
