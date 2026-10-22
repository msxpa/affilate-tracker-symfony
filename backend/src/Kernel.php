<?php
#affiliate-tracker-symfony/backend/src/Kernel.php
// namespace App;

// use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
// use Symfony\Component\HttpKernel\Kernel as BaseKernel;

// class Kernel extends BaseKernel
// {
//     use MicroKernelTrait;
// }

namespace App {
    use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
    use Symfony\Component\HttpKernel\Kernel as BaseKernel;

    class Kernel extends BaseKernel
    {
        use MicroKernelTrait;
    }
}

// Переключаемся в глобальный namespace (без имени)
namespace {
    if (!function_exists('debug_logx')) {
        /**
         * Пишет var_dump в отдельный файл debugx.log в корне проекта.
         * Работает в любом месте приложения.
         */
        function debug_logx(mixed $value, string $label = ''): void
        {
            // Путь к файлу: backend/var/log/debugx.log (или public, как вы хотите)
            $logFile = dirname(__DIR__) . '/var/log/debugx.log';
            
            // Создаём директорию, если её нет
            if (!is_dir(dirname($logFile))) {
                mkdir(dirname($logFile), 0777, true);
            }

            $output = $label !== '' ? "[{$label}] " : '';
            
            ob_start();
            var_dump($value);
            $output .= ob_get_clean();
            $output .= "\n---------------------------------\n";

            file_put_contents($logFile, $output, FILE_APPEND | LOCK_EX);
        }
    }
}
