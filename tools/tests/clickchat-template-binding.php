<?php
// Standalone contract test. No framework bootstrap, network, or database writes.
namespace Illuminate\Support\Facades {
    class Schema { public static function hasColumn(...$args) { return true; } }
}
namespace App\Models {
    class SmsTemplate {
        public static array $rows = [];
        public static function active() { return new Query(self::$rows); }
    }
    class Query {
        public function __construct(private array $rows) {}
        public function whereNotNull($field) { return $this; }
        public function when($id, $callback) { return $id ? $callback($this) : $this; }
        public function whereKey($id) { $this->rows = array_filter($this->rows, fn ($r) => $r->id == $id); return $this; }
        public function get() { return $this->rows; }
    }
}
namespace {
    function data_get($data, $key, $default = null) {
        foreach (explode('.', $key) as $part) { $data = $data[$part] ?? null; }
        return $data ?? $default;
    }
    require __DIR__.'/../../app/Services/WhatsApp/ClickChatTemplateBinding.php';
    $binding = new \App\Services\WhatsApp\ClickChatTemplateBinding;
    $row = (object) ['id' => 1, 'message' => 'Hello {client_name}, contract {contract_number}',
        'clickchat_binding' => ['name' => 'contract_update', 'language' => 'ar', 'variables' => ['client_name', 'contract_number']]];
    \App\Models\SmsTemplate::$rows = [$row];
    $assert = function ($condition, $label) { if (!$condition) throw new \RuntimeException($label); echo "PASS: $label\n"; };
    $assert($binding->resolve('Hello Ahmed, contract 1001', [])['vars'] === ['Ahmed', '1001'], 'legacy rendered contract variables');
    $assert($binding->resolve('different configured message', ['template_id' => 1,
        'template_variables' => ['client_name' => 'Ali', 'contract_number' => '5']])['vars'] === ['Ali', '5'], 'explicit alert variables take precedence');
    $assert($binding->resolve('unrelated message', []) === null, 'unrelated messages unchanged');
    $assert($binding->resolve('Hello Ahmed, contract 1001', ['template_id' => 2]) === null, 'explicit unbound template unchanged');
    try { $binding->resolve('ignored', ['template_id' => 1, 'template_variables' => ['client_name' => 'Ali']]); throw new \LogicException('missing accepted'); }
    catch (\RuntimeException $e) { $assert(str_contains($e->getMessage(), 'contract_number'), 'missing variable blocks send'); }
    \App\Models\SmsTemplate::$rows = [$row, clone $row];
    try { $binding->resolve('Hello Ahmed, contract 1001', []); throw new \LogicException('ambiguous accepted'); }
    catch (\RuntimeException $e) { $assert(str_contains($e->getMessage(), 'أكثر'), 'ambiguous binding blocks send'); }
}
