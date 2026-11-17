<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $page, array $params = []): string
{
    return '?' . http_build_query(['page' => $page] + $params);
}

function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params), true, 303);
    exit;
}

function fail(int $code, string $message): never
{
    http_response_code($code);
    echo h($message);
    exit;
}

function field(string $key, int $max = 100, bool $required = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value) || strlen(trim($value)) > $max || ($required && trim($value) === '')) {
        throw new DomainException('Periksa isian ' . str_replace('_', ' ', $key) . '.');
    }
    return trim($value);
}

function number(string $key, int $min = 1, int $max = 2147483647): int
{
    $value = filter_var($_POST[$key] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    if ($value === false) {
        throw new DomainException('Periksa angka ' . str_replace('_', ' ', $key) . '.');
    }
    return $value;
}

function choice(string $key, array $choices): string
{
    $value = field($key);
    if (!in_array($value, $choices, true)) {
        throw new DomainException('Pilihan tidak valid.');
    }
    return $value;
}

function cents(string $amount): int
{
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $amount)) {
        throw new DomainException('Nominal tidak valid. Gunakan titik untuk desimal.');
    }
    [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');
    return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
}

function decimal(int $value): string
{
    if ($value < 0 || $value > 999999999999) {
        throw new DomainException('Nominal di luar batas penyimpanan.');
    }
    return intdiv($value, 100) . '.' . str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
}

function rupiah(mixed $value): string
{
    return $value === null ? '—' : 'Rp ' . number_format((float) $value, 2, ',', '.');
}

function linkTo(string $label, string $page, array $params = []): string
{
    return '<a href="' . h(url($page, $params)) . '">' . h($label) . '</a>';
}

function formStart(string $action): void
{
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h($_SESSION['csrf'])
        . '"><input type="hidden" name="action" value="' . h($action) . '">';
}

function hidden(string $key, mixed $value): void
{
    echo '<input type="hidden" name="' . h($key) . '" value="' . h($value) . '">';
}

function input(string $key, string $label, string $type = 'text', mixed $value = '', bool $required = true): void
{
    echo '<label>' . h($label) . '<input name="' . h($key) . '" type="' . h($type) . '" value="' . h($value) . '"'
        . ($required ? ' required' : '') . ($type === 'number' ? ' step="1" min="0"' : '') . '></label>';
}

function selectField(string $key, string $label, array $options, mixed $selected = '', string $placeholder = 'Pilih'): void
{
    echo '<label>' . h($label) . '<select name="' . h($key) . '"><option value="">' . h($placeholder) . '</option>';
    foreach ($options as $value => $name) {
        echo '<option value="' . h($value) . '"' . ((string) $selected === (string) $value ? ' selected' : '') . '>' . h($name) . '</option>';
    }
    echo '</select></label>';
}

function button(string $label): void
{
    echo '<button type="submit">' . h($label) . '</button></form>';
}

function table(array $headers, array $data, ?callable $action = null): void
{
    echo '<div class="scroll"><table><thead><tr>';
    foreach ($headers as $header) {
        echo '<th>' . h($header) . '</th>';
    }
    echo '</tr></thead><tbody>';
    if (!$data) {
        echo '<tr><td colspan="' . count($headers) . '">Belum ada data.</td></tr>';
    }
    foreach ($data as $record) {
        echo '<tr>';
        foreach ($record as $value) {
            echo '<td>' . h($value ?? '—') . '</td>';
        }
        if ($action) {
            echo '<td>' . $action($record) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
