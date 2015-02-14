{include file="header.tpl"}

<table border="1" cellpadding="5">
<tr><td>Тема</td>
<td>Дата начала</td>
<td>Дата закрытия</td><td>Автор</td><td>Статус</td><td>Количество работ</td><td>Награда</td>
<td>Предыдущий конкурс</td>
<td>Действие</td></tr>
{section name=contest loop=$contests}
<tr>
<td><a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?id={$contests[contest].id}&type=contest&action=view">{$contests[contest].subject}</a></td>
<td>{$contests[contest].open_date}</td>
<td>{$contests[contest].close_date}</td>
<td>{$contests[contest].display_name}</td>
<td>
{if $contests[contest].status eq 0}
    Закрыт
{elseif $contests[contest].status eq 1}
    Прием работ
{elseif $contests[contest].status eq 2}
    Голосование
{/if}
</td>
<td>{$contests[contest].works}</td>
<td>{$contests[contest].rewards}</td>
<td><a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?id={$contests[contest].prev_id}&type=contest&action=view">{$contests[contest].prev_id}</a></td>
<td>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?id={$contests[contest].id}&type=contest&action=edit">Редактировать</a> |
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?id={$contests[contest].id}&type=contest&action=delete">Удалить</a>
</td>
</tr>
{/section}
</table>
<br>
<br>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=contest&action=add">Новый конкурс</a>
<br>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=view">Управление пользователями</a>

{include file="footer.tpl"}