{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}">К списку конкурсов</a><br><br>
<table border="1" cellpadding="5">
<tr>
<td>Пользователь</td>
<td>Отображаемое имя</td>
<td>Баланс</td>
<td>Группа</td>
<td>Деньги</td>
<td>DC</td>
<td>Instagram</td>
<td>Действие</td>
</tr>
{section name=user loop=$users}
<tr>
<td>{$users[user].user_id}</td>
<td>{$users[user].display_name}</td>
<td>{$users[user].balance}</td>
<td>{$users[user].group}</td>
<td>{$users[user].money}</td>
<td>{$users[user].dc}</td>
<td>{$users[user].insta}</td>
<td>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=edit&id={$users[user].id}">Редактировать</a> |
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=delete&id={$users[user].id}" onclick="return confirm('Удалить?');">Удалить</a>
</td>
</tr>
{/section}
</table>
<br>
<strong>Всего пользователей: {$count}</strong>

<br>
<br>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=add">Новый пользователь</a>

{include file="footer.tpl"}