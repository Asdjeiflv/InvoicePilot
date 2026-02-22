<?php

/**
 * PHPUnit 12対応: doc-comment形式のアノテーションを属性に変換
 */

$testDir = __DIR__ . '/../tests/Feature';
$files = glob($testDir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;

    // use文が存在するか確認
    $hasTestAttribute = strpos($content, 'use PHPUnit\Framework\Attributes\Test;') !== false;

    // /** @test */ を #[Test] に置換
    $content = preg_replace('/    \/\*\* @test \*\/\n/', "    #[Test]\n", $content);

    // use文を追加（まだ存在しない場合）
    if (!$hasTestAttribute && $content !== $originalContent) {
        // use文を探して、TestCaseの前に挿入
        $content = preg_replace(
            '/(use Illuminate\\\\Foundation\\\\Testing\\\\RefreshDatabase;\n)/s',
            "$1use PHPUnit\\Framework\\Attributes\\Test;\n",
            $content
        );

        // RefreshDatabaseがない場合は、他のuse文の後に挿入
        if ($content === $originalContent) {
            $content = preg_replace(
                '/(use Tests\\\\TestCase;\n)/s',
                "use PHPUnit\\Framework\\Attributes\\Test;\n$1",
                $content
            );
        }
    }

    // 変更があった場合のみファイルを更新
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✓ Updated: " . basename($file) . "\n";
    } else {
        echo "- Skipped: " . basename($file) . " (no changes needed)\n";
    }
}

echo "\n✅ All test files processed!\n";
