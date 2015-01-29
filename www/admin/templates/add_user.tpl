{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=view">К списку пользователей</a><br><br>

<form method="POST" action="{$SCRIPT_NAME}?action=add&type=user">
<table border="1" cellpadding="5">
<tr>
<td>Пользователь</td>
<td>Отображаемое имя</td>
<td>Баланс</td>
<td>Группа</td>
<td>Деньги</td>
<td>DC</td>
<td>Пароль</td>
</tr>
<tr>
<td><input type="text" name="user_id" value=""></td>
<td><input type="text" name="display_name" value=""></td>
<td><input type="text" name="balance" value="0"></td>
<td><select name="group">{html_options values=$group_option_values selected=$group_option_selected output=$group_option_output}</select></td>
<td><input type="text" name="money" value="0"></td>
<td><input type="text" name="dc" value="0"></td>
<td><input type="text" name="password" value=""></td></tr>
<tr>
<td colspan=7 align=center><input type="submit" name="submit" value="OK"></td>
</tr>
</table>
</form>

{include file="footer.tpl"}