# luler/shm_cache
使用Linux的shm来做缓存,默认会使用/dev/shm/app_name来做存储位置，所以fastcgi模式下，需要保证目录/dev/shm/包含在open_basedir配置里，否则file_get_contents/file_put_contents函数无法使用
# 助手类列表如下
- ShmCache
