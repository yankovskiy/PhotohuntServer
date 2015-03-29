{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}">К списку конкурсов</a><br><br>
<table border="1" cellpadding="5">
<tr>
<td>От</td>
<td>Кому</td>
<td>Дата</td>
<td>Тема</td>
<td>Текст</td>
<td>Входящие</td>
<td>Исходящие</td>
<td>Статус</td>
<td>Действие</td>
</tr>
{section name=message loop=$messages}
<tr>
<td>{$messages[message].from} ({$messages[message].from_email})</td>
<td>{$messages[message].to} ({$messages[message].to_email})</td>
<td>{$messages[message].date}</td>
<td>{$messages[message].title}</td>
<td>{$messages[message].message}</td>
<td>{$messages[message].inbox}</td>
<td>{$messages[message].outbox}</td>
<td>
{if $messages[message].status eq 0}
    Не отправлено
{elseif $messages[message].status eq 1}
    Отправлено
{elseif $messages[message].status eq 2}
    Прочитано
{/if}
</td>
<td>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=message&action=edit&id={$messages[message].id}">Редактировать</a> |
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=message&action=delete&id={$messages[message].id}" onclick="return confirm('Удалить?');">Удалить</a>
</td>
</tr>
{/section}
</table>
<br>
<strong>Всего сообщений: {$count}</strong>

<br>
<br>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=message&action=add">Новое сообщение</a>

{include file="footer.tpl"}