<tr>
    <td class="is-label">{{ $change['label'] }}</td>
    @if ($isUpdated)
        <td class="is-old"><span>{{ $change['old_display'] }}</span></td>
        <td class="is-new">{{ $change['new_display'] }}</td>
    @else
        <td>{{ $change['new_display'] !== 'غير محدد' ? $change['new_display'] : $change['old_display'] }}</td>
    @endif
</tr>
