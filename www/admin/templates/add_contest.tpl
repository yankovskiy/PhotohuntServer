{include file="header.tpl"}

<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}">К списку конкурсов</a><br><br>
<form method="POST" action="{$SCRIPT_NAME}?action=add&type=contest">
<table border=0 cellpadding=5>
<tr>
<td>Тема</td>
<td>Дата закрытия</td>
<td>Автор</td>
<td>Статус</td>
<td>Награда</td>
</tr>
<tr>
<td><input type="text" name="subject" value=""></td>
<td><input type="text" name="close_date" value="{$close_date}"></td>
<td><select name="user_id">{html_options values=$user_option_values selected=$user_option_selected output=$user_option_output}</select></td>
<td><select name="status">{html_options values=$status_option_values selected=$status_option_selected output=$status_option_output}</select></td>
<td><input type="text" name="rewards" value="5"></td>
</tr>
<tr><td colspan=6 align=center><input type="submit" name="submit" value="OK"></td>
</table>
</form>

{include file="footer.tpl"}