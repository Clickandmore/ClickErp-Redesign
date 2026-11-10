<?php
namespace App\Services\WhatsApp;

use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Optional bindings are evaluated only by the Click Live Chat transport. */
class ClickChatTemplateBinding
{
    public function resolve(string $message, array $context): ?array
    {
        if (!Schema::hasColumn('sms_templates', 'clickchat_binding')) return null;
        $id = $context['template_id'] ?? data_get($context, 'meta.template_id');
        $templates = SmsTemplate::active()->whereNotNull('clickchat_binding')
            ->when($id, fn ($q) => $q->whereKey($id))->get();
        $matches = [];
        foreach ($templates as $template) {
            $binding = $template->clickchat_binding;
            if (empty($binding['name'])) continue;
            $values = $id ? ($context['template_variables'] ?? []) : [];
            if (!$values) {
                // Legacy callers supply rendered text. Match the complete local template,
                // never an arbitrary substring, and reject ambiguous bindings.
                $keys = [];
                $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $template->message, -1, PREG_SPLIT_DELIM_CAPTURE);
                $pattern = '';
                foreach ($parts as $part) {
                    if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m)) {
                        $keys[] = $m[1]; $pattern .= '(.*?)';
                    } else { $pattern .= preg_quote($part, '~'); }
                }
                if (!preg_match('~\A'.$pattern.'\z~us', $message, $captures)) {
                    if ($id) throw new RuntimeException('نص الرسالة لا يطابق القالب المرتبط باللايف شات.');
                    continue;
                }
                foreach ($keys as $i => $key) {
                    if (isset($values[$key]) && $values[$key] !== $captures[$i+1]) throw new RuntimeException('قيمة متغير القالب غير متطابقة.');
                    $values[$key] = $captures[$i+1];
                }
            }
            $vars = [];
            foreach ($binding['variables'] ?? [] as $key) {
                if (!isset($values[$key]) || !is_scalar($values[$key]) || trim((string) $values[$key]) === '') {
                    throw new RuntimeException('قيمة متغير قالب اللايف شات مفقودة: '.$key);
                }
                $vars[] = (string) $values[$key];
            }
            $matches[] = ['name' => $binding['name'], 'language' => $binding['language'], 'vars' => $vars];
        }
        if (count($matches) > 1) throw new RuntimeException('تطابق النص مع أكثر من قالب؛ حدد قالب الرسالة قبل الإرسال.');
        return $matches[0] ?? null;
    }
}
