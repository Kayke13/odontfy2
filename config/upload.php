<?php
class Upload {
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const UPLOAD_DIR = 'uploads/';
    
    public static function uploadImage($file, $type = 'dentista') {
        // Verificar se o arquivo foi enviado
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload do arquivo.'];
        }
        
        // Verificar tamanho
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.'];
        }
        
        // Verificar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.'];
        }
        
        // Criar diretório se não existir
        $uploadDir = self::UPLOAD_DIR . $type . 's/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true, 
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => $filepath
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao salvar arquivo.'];
        }
    }
    
    public static function deleteImage($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return true;
    }
    
    public static function getImageUrl($filename, $type = 'dentista', $name = '') {
        if (empty($filename)) {
            return 'data:image/svg+xml;base64,' . base64_encode(self::getInitialsAvatarSvg($name));
        }
        return self::UPLOAD_DIR . $type . 's/' . $filename;
    }
    
    public static function getInitialsAvatarSvg($name) {
        // Extrair iniciais do nome
        $initials = self::getInitials($name);
        
        // Gerar cor baseada no nome (para consistência)
        $color = self::getColorFromName($name);
        
        return '<svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="50" fill="' . $color . '"/>
            <text x="50" y="60" font-family="Arial, sans-serif" font-size="36" font-weight="bold" text-anchor="middle" fill="white">' . $initials . '</text>
        </svg>';
    }
    
    public static function getInitials($name) {
        if (empty($name)) {
            return '?';
        }
        
        $words = explode(' ', trim($name));
        $initials = '';
        
        // Pegar primeira letra de cada palavra (máximo 2)
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 2) break;
            }
        }
        
        return $initials ?: strtoupper(substr($name, 0, 1));
    }
    
    public static function getColorFromName($name) {
        if (empty($name)) {
            return '#6B7280'; // Cinza padrão
        }
        
        // Cores predefinidas para avatares
        $colors = [
            '#EF4444', // Vermelho
            '#F97316', // Laranja
            '#EAB308', // Amarelo
            '#22C55E', // Verde
            '#06B6D4', // Ciano
            '#3B82F6', // Azul
            '#8B5CF6', // Roxo
            '#EC4899', // Rosa
            '#84CC16', // Verde lima
            '#F59E0B', // Âmbar
        ];
        
        // Usar hash do nome para escolher cor consistentemente
        $hash = crc32($name);
        $index = abs($hash) % count($colors);
        
        return $colors[$index];
    }
    
    public static function getWhatsAppLink($telefone) {
        $numeros = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($numeros) >= 10 && substr($numeros, 0, 2) !== '55') {
            $numeros = '55' . $numeros;
        }
        return 'https://wa.me/' . $numeros;
    }
}
?>
