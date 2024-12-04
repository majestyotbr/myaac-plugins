<?php
defined('MYAAC') or die('Direct access not allowed!');

require PLUGINS . 'guild-wars/init.php';

$guild_id = (int) $_REQUEST['guild'];
$war_id = (int) $_REQUEST['war'];

if(!$logged) {
	$errors[] = 'You are not logged.';
}

if(!empty($errors))
{
	$twig->display('error_box.html.twig', ['errors' => $errors]);
	$twig->display('guilds.back_button.html.twig');
	return;
}

$guild = new OTS_Guild($guild_id);
if(!$guild->isLoaded()) {
	$errors[] = "Guild with ID <b>$guild_id</b> doesn't exist.";
}

if(empty($errors)) {
	$guild_leader_char = $guild->getOwner();
	$guild_leader = false;
	$account_players = $account_logged->getPlayers();

	foreach($account_players as $player) {
		if($guild_leader_char->getId() == $player->getId()) {
			$guild_leader = TRUE;
		}
	}

	if($guild_leader) {
		$war = new OTS_GuildWar($war_id);
		if(!$war->isLoaded())
			$errors[] = 'War with ID <b>'.$war_id.'</b> doesn\'t exist.';

		if ($hasGuildWarsFragLimitColumn && $hasGuildWarsBountyColumn) {
			$bounty = $war->getCustomField('bounty');
			if ($guild->getCustomField('balance') < $bounty) {
				$errors[] = "Your guild does not have that much money in the bank account balance to accept that war with the bounty of $bounty gold.";
			}
		}

		if(!empty($errors)) {
			$twig->display('error_box.html.twig', ['errors' => $errors]);
			$twig->display('guilds.back_button.html.twig');
			return;
		}

		if(empty($errors)) {
			if($war->getGuild2ID() != $guild->getID() || $war->getStatus() != OTS_GuildWar::STATE_INVITED) {
				$errors[] = 'Your guild is not invited to that war.';
			}

			if(empty($errors)) {
				$war->setStatus(OTS_GuildWar::STATE_ON_WAR);
				$war->save();

				if ($hasGuildWarsStartedColumn) {
					$war->setCustomField('started', time());
					$war->setCustomField('ended', 0);
				}

				if ($hasGuildWarsFragLimitColumn && $hasGuildWarsBountyColumn) {
					// reduce bounty from guild balance
					$guild->setCustomField('balance', (int)$guild->getCustomField('balance') - (int)$bounty);
				}

				header('Location: '. getGuildLink($guild->getName(), false));
				echo 'War invitation accepted. Redirecting...';
			}
		}
	}
	else
		$errors[] = 'You are not a leader of guild!';
}

if(!empty($errors))
{
	$twig->display('error_box.html.twig', ['errors' => $errors]);
	$twig->display('guilds.back_button.html.twig');
}
