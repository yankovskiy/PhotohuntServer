{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=message&action=view">К списку сообщений</a><br><br>

<form method="POST" action="{$SCRIPT_NAME}?action=edit&type=message&id={$message.id}">
<table border="1" cellpadding="5">
<tr>
<td>От</td>
<td>Кому</td>
<td>Дата</td>
<td>Тема</td>
<td>Текст</td>
<td>Статус</td>
</tr>
<tr>
<td><select name="from_user_id">{html_options values=$from_users_values selected=$from_users_selected output=$from_users_output}</select></td>
<td><select name="to_user_id">{html_options values=$to_users_values selected=$to_users_selected output=$to_users_output}</select></td>
<td><input type="text" name="date" value="{$message.date}"></td>
<td><input type="text" name="title" value="{$message.title}"></td>
<td><input type="text" name="message" value="{$message.message}"></td>
<td><input type="text" name="status" value="{if $message.status eq 0}
    Не отправлено
{elseif $message.status eq 1}
    Отправлено
{elseif $message.status eq 2}
    Прочитано
{/if}" readonly></td>
</tr>
<tr>
<td colspan=6 align=center><input type="submit" name="submit" value="OK"></td>
</tr>
</table>
</form>

{include file="footer.tpl"}