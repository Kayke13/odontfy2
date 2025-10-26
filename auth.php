<?php
require_once 'config/database.php';

class Auth {
    public static function login($email, $password) {
        $dentista = Database::find('dentistas', 'email', $email);
        
        if ($dentista && password_verify($password, $dentista['password'])) {
            $_SESSION['dentista_id'] = $dentista['id'];
            $_SESSION['dentista_nome'] = $dentista['nome'];
            $_SESSION['dentista_email'] = $dentista['email'];
            $_SESSION['dentista_username'] = $dentista['username'];
            return true;
        }
        
        return false;
    }
    
    public static function register($data) {
        // Verificar se email já existe
        if (Database::find('dentistas', 'email', $data['email'])) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }
        
        // Verificar se username já existe
        if (Database::find('dentistas', 'username', $data['username'])) {
            return ['success' => false, 'message' => 'Username já cadastrado'];
        }
        
        // Processar upload da foto do consultório
        $foto_consultorio = '';
        if (isset($_FILES['foto_consultorio']) && $_FILES['foto_consultorio']['error'] === UPLOAD_ERR_OK) {
            require_once 'config/upload.php';
            $upload_result = Upload::uploadImage($_FILES['foto_consultorio'], 'dentista', $data['nome']);
            if ($upload_result['success']) {
                $foto_consultorio = $upload_result['filename'];
            }
        }
        
        // Criar dentista
        $dentista = [
            'nome' => $data['nome'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'clinica' => $data['clinica'],
            'telefone' => $data['telefone'],
            'cro' => $data['cro'],
            'especialidade' => $data['especialidade'],
            'endereco' => $data['endereco'],
            'cidade' => $data['cidade'],
            'estado' => $data['estado'],
            'cep' => $data['cep'],
            'foto' => $foto_consultorio,
            'plano' => 'teste',
            'assinatura_ativa' => false,
            'teste_inicio' => date('Y-m-d'),
            'teste_fim' => date('Y-m-d', strtotime('+7 days')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'horarios' => [
                'segunda' => ['inicio' => '08:00', 'fim' => '18:00', 'ativo' => true],
                'terca' => ['inicio' => '08:00', 'fim' => '18:00', 'ativo' => true],
                'quarta' => ['inicio' => '08:00', 'fim' => '18:00', 'ativo' => true],
                'quinta' => ['inicio' => '08:00', 'fim' => '18:00', 'ativo' => true],
                'sexta' => ['inicio' => '08:00', 'fim' => '18:00', 'ativo' => true],
                'sabado' => ['inicio' => '08:00', 'fim' => '12:00', 'ativo' => false],
                'domingo' => ['inicio' => '08:00', 'fim' => '12:00', 'ativo' => false]
            ],
            'servicos' => [
                ['nome' => 'Consulta', 'duracao' => 30, 'preco' => 0, 'ativo' => true],
                ['nome' => 'Limpeza', 'duracao' => 60, 'preco' => 0, 'ativo' => true],
                ['nome' => 'Restauração', 'duracao' => 60, 'preco' => 0, 'ativo' => true]
            ]
        ];
        
        $result = Database::insert('dentistas', $dentista);
        
        if ($result) {
            $_SESSION['dentista_id'] = $result['id'];
            $_SESSION['dentista_nome'] = $result['nome'];
            $_SESSION['dentista_email'] = $result['email'];
            $_SESSION['dentista_username'] = $result['username'];
            return ['success' => true, 'message' => 'Cadastro realizado com sucesso!'];
        }
        
        return ['success' => false, 'message' => 'Erro ao cadastrar'];
    }
    
    public static function logout() {
        session_destroy();
        return true;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['dentista_id']);
    }
    
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return Database::find('dentistas', 'id', $_SESSION['dentista_id']);
        }
        return null;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}
?>
