{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=message&action=view">К списку сообщений</a><br><br>

<form method="POST" action="{$SCRIPT_NAME}?action=add&type=message">
<table border="1" cellpadding="5">
<tr>
<td>От</td>
<td>Кому</td>
<td>Дата</td>
<td>Тема</td>
<td>Текст</td>
</tr>
<tr>
<td><select name="from_user_id">{html_options values=$from_users_values selected=$from_users_selected output=$from_users_output}</select></td>
<td><select name="to_user_id">{html_options values=$to_users_values selected=$to_users_selected output=$to_users_output}</select></td>
<td><input type="text" name="date" value="{$date}"></td>
<td><input type="text" name="title" value=""></td>
<td><textarea name="message"></textarea></td>
</tr>
<tr>
<td colspan=5 align=center><input type="submit" name="submit" value="OK"></td>
</tr>
</table>
</form>

{include file="footer.tpl"}