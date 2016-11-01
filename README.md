# DISCLAIMER

This is yii2 component for OpenStack Swift object storage

# CONFIGURATION

config.php:
```
'components' => [
    'mystorage' => [
        'class' => 'alexsalt\swiftstorage\StorageComponent',
        'authUrl' => 'https://some.auth-url.org',
        'username' => 'swift',
        'password' => 'swift',
        'container' => 'mybucket',
    ],
]
```
