<?php
// Simulação de banco de dados usando arquivos JSON
class Database {
    private static $dataDir = null;
    
    private static function getDataDir() {
        if (self::$dataDir === null) {
            // Caminho absoluto para a pasta data na raiz do projeto
            self::$dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        }
        return self::$dataDir;
    }
    
    public static function init() {
        $dataDir = self::getDataDir();
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        
        // Criar arquivos JSON se não existirem
        $files = [
            'dentistas.json' => [],
            'agendamentos.json' => [],
            'configuracoes.json' => []
        ];
        
        foreach ($files as $file => $default) {
            $path = $dataDir . $file;
            if (!file_exists($path)) {
                file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT));
            }
        }
    }
    
    public static function get($table) {
        $file = self::getDataDir() . $table . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return [];
    }
    
    public static function save($table, $data) {
        $file = self::getDataDir() . $table . '.json';
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public static function find($table, $field, $value) {
        $data = self::get($table);
        foreach ($data as $item) {
            if (isset($item[$field]) && $item[$field] === $value) {
                return $item;
            }
        }
        return null;
    }
    
    public static function insert($table, $item) {
        $data = self::get($table);
        $item['id'] = uniqid();
        $item['created_at'] = date('Y-m-d H:i:s');
        $data[] = $item;
        self::save($table, $data);
        return $item;
    }
    
    public static function update($table, $id, $updates) {
        $data = self::get($table);
        foreach ($data as $key => $item) {
            if ($item['id'] === $id) {
                $data[$key] = array_merge($item, $updates);
                $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                self::save($table, $data);
                return $data[$key];
            }
        }
        return null;
    }
    
    public static function delete($table, $id) {
        $data = self::get($table);
        foreach ($data as $key => $item) {
            if ($item['id'] === $id) {
                unset($data[$key]);
                self::save($table, array_values($data));
                return true;
            }
        }
        return false;
    }
}

// Inicializar banco de dados
Database::init();
?>
