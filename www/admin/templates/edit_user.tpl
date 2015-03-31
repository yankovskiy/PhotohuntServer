{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=user&action=view">К списку пользователей</a><br><br>

<span style="color:red"><strong>При изменении емайла обязательно нужно задать новый пароль, иначе пользователь будет заблокирован!</strong></span><br>
<form method="POST" action="{$SCRIPT_NAME}?id={$user.id}&action=edit&type=user">
<table border="1" cellpadding="5">
<tr>
<td>Пользователь</td>
<td>Отображаемое имя</td>
<td>Баланс</td>
<td>Группа</td>
<td>Деньги</td>
<td>DC</td>
<td>Instagram</td>
<td>Новый пароль</td>
</tr>
<tr>
<td><input type="text" name="user_id" value="{$user.user_id}"></td>
<td><input type="text" name="display_name" value="{$user.display_name}"></td>
<td><input type="text" name="balance" value="{$user.balance}"></td>
<td><select name="group">{html_options values=$group_option_values selected=$group_option_selected output=$group_option_output}</select></td>
<td><input type="text" name="money" value="{$user.money}"></td>
<td><input type="text" name="dc" value="{$user.dc}"></td>
<td><input type="text" name="insta" value="{$user.insta}"></td>
<td><input type="text" name="password" value=""></td></tr>
<tr>
<td colspan=8 align=center><input type="submit" name="submit" value="OK"></td>
</tr>
</table>
</form>
<br>

<table border="1" cellpadding="5">
<tr>
<td>Фото</td>
<td>Конкурс</td>
<td>Тема следующего конкурса</td>
<td>Количество голосов</td>
</tr>
{section name=img loop=$images}
<tr>
<td><a href="http://{$smarty.server.SERVER_NAME}/images/{$images[img].id}.jpg"><img src="http://{$smarty.server.SERVER_NAME}/images/{$images[img].id}.jpg" width="30%" height="30%"></a></td>
<td><a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?type=contest&action=view&id={$images[img].contest_id}">{$images[img].contest_subject}</a></td>
<td>{$images[img].subject}</td>
<td>{$images[img].vote_count}</td>
</tr>
{/section}
</table>

{include file="footer.tpl"}