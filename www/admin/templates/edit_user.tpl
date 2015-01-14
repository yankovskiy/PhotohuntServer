{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=view">К списку пользователей</a><br><br>

<span style="color:red"><strong>При изменении емайла обязательно нужно задать новый пароль, иначе пользователь будет заблокирован!</strong></span><br>
<form method="POST" action="{$SCRIPT_NAME}?id={$user.id}&action=edit&type=user">
<table border="1" cellpadding="5">
<tr>
<td>Пользователь</td>
<td>Отображаемое имя</td>
<td>Баланс</td>
<td>Очков голосования</td>
<td>Группа</td>
<td>Новый пароль</td>
</tr>
<tr>
<td><input type="text" name="user_id" value="{$user.user_id}"></td>
<td><input type="text" name="display_name" value="{$user.display_name}"></td>
<td><input type="text" name="balance" value="{$user.balance}"></td>
<td><input type="text" name="vote_count" value="{$user.vote_count}"></td>
<td><select name="group">{html_options values=$group_option_values selected=$group_option_selected output=$group_option_output}</select></td>
<td><input type="text" name="password" value=""></td></tr>
<tr>
<td colspan=6 align=center><input type="submit" name="submit" value="OK"></td>
</tr>
</table>
</form>
<br>
<br>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=add">Новый пользователь</a>

{include file="footer.tpl"}