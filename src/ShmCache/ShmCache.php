<?php

namespace Luler\ShmCache;

class ShmCache
{
    public static $shm_root_path = '/dev/shm/';
    public static $app_name = null;
    private static $timeout_check_setting_key = '_timeout_check_setting_key_';
    private static $timeout_check_setting_interval = 60;

    /**
     * 设置缓存
     * @param string $name //键名
     * @param $value //键值
     * @param int $expire //超时时间
     * @return false|int
     * @author LinZhou <1207032539@qq.com>
     * @throws \Exception
     */
    public static function set(string $name, $value, int $expire = 0)
    {
        $data = [
            'value' => $value,
            'expire' => $expire,
        ];
        if ($expire > 0) {
            $data['expire'] += time();
        }
        if (!preg_match('/^\w+$/', $name, $matchs)) {
            throw new \Exception('键名设置不规范,仅支持字母、数字、下划线');
        }
        if (empty(self::$app_name)) {
            throw new \Exception('请设置应用缓存标志');
        }
        if ($name !== self::$timeout_check_setting_key) {
            self::delExpiredKey();
        }
        $file_path = self::$shm_root_path . self::$app_name;
        if (!file_exists($file_path)) {
            mkdir($file_path, 0777, true);
            chmod($file_path, 0777);
        }
        $file_path .= '/' . $name;
        $data = serialize($data);
        file_put_contents($file_path, $data);
        chmod($file_path, 0777);
        return true;
    }

    /**
     * 获取缓存
     * @param string $name //键名
     * @param bool $default
     * @return bool
     * @author LinZhou <1207032539@qq.com>
     * @throws \Exception
     */
    public static function get(string $name, $default = false)
    {
        if (!preg_match('/^\w+$/', $name, $matchs)) {
            throw new \Exception('键名设置不规范,仅支持字母、数字、下划线');
        }
        if (empty(self::$app_name)) {
            throw new \Exception('请设置应用缓存标志');
        }
        if ($name !== self::$timeout_check_setting_key) {
            self::delExpiredKey();
        }
        $file_path = self::$shm_root_path . self::$app_name . '/' . $name;
        if (!file_exists($file_path)) {
            return $default;
        }
        $data = file_get_contents($file_path);
        if (empty($data)) {
            return $default;
        }
        $data = unserialize($data);
        if ($data['expire'] !== 0 && $data['expire'] < time()) {
            return $default;
        }
        return $data['value'];
    }

    /**
     * 判断缓存键名是否存在
     * @param $name
     * @return bool
     * @throws \Exception
     * @author LinZhou <1207032539@qq.com>
     */
    public static function has($name)
    {
        if (!preg_match('/^\w+$/', $name, $matchs)) {
            throw new \Exception('键名设置不规范,仅支持字母、数字、下划线');
        }
        if (empty(self::$app_name)) {
            throw new \Exception('请设置应用缓存标志');
        }
        if ($name !== self::$timeout_check_setting_key) {
            self::delExpiredKey();
        }
        $file_path = self::$shm_root_path . self::$app_name . '/' . $name;
        if (!file_exists($file_path)) {
            return false;
        }
        return true;
    }

    /**
     * 删除缓存
     * @param $name
     * @return bool
     * @throws \Exception
     * @author LinZhou <1207032539@qq.com>
     */
    public static function rm($name)
    {
        if (!preg_match('/^\w+$/', $name, $matchs)) {
            throw new \Exception('键名设置不规范,仅支持字母、数字、下划线');
        }
        if (empty(self::$app_name)) {
            throw new \Exception('请设置应用缓存标志');
        }
        if ($name !== self::$timeout_check_setting_key) {
            self::delExpiredKey();
        }
        $file_path = self::$shm_root_path . self::$app_name . '/' . $name;
        if (!file_exists($file_path)) {
            return false;
        }
        return unlink($file_path);
    }

    /**
     * 模糊删除某个包含某个字符的所有键值对(危险方法，仅做调试使用，切勿滥用)
     * @param $str
     * @param int $search_or_delete 0-搜索键名，1-删除搜索到的键名
     * @return bool
     * @throws \Exception
     * @author LinZhou <1207032539@qq.com>
     */
    public static function searchOrRemoveKeyIfContainStr($str, $search_or_delete = 0)
    {
        if (empty(self::$app_name)) {
            throw new \Exception('请设置应用缓存标志');
        }
        $file_path = self::$shm_root_path . self::$app_name;
        $files = glob($file_path . '/*' . $str . '*');
        $total = 0;
        foreach ($files as $file) {
            $base_file = basename($file);
            if ($search_or_delete === 1) {
                self::rm($base_file);
                echo '已删除：' . $base_file . "\n";
            } else {
                echo '找到键名：' . $base_file . "\n";
            }
            $total++;
        }
        echo '共匹配或处理键值对数量：' . $total . "\n";
    }

    /**
     * 定期检查过期缓存并删除
     * @throws \Exception
     * @author LinZhou <1207032539@qq.com>
     */
    public static function delExpiredKey()
    {
        if (empty(self::$app_name)) {
            throw new \Exception('请设置应用缓存标志');
        }
        $timeout = self::get(self::$timeout_check_setting_key);
        if ($timeout === false || $timeout < time()) {
            $file_path = self::$shm_root_path . self::$app_name;
            $files = glob($file_path . '/*');
            foreach ($files as $file) {
                $data = file_get_contents($file);
                $data = unserialize($data);
                if (!isset($data['expire']) || ($data['expire'] !== 0 && $data['expire'] < time())) {
                    @unlink($file);
                }
            }
            self::set(self::$timeout_check_setting_key, time() + self::$timeout_check_setting_interval);
        }
    }
}
