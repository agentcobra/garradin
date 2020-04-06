<?php

namespace Garradin\Compta;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Config;

class Import
{
	protected $header = [
		'Numéro mouvement',
		'Date',
		'Type de mouvement',
		'Catégorie',
		'Libellé',
		'Montant',
		'Compte de débit - numéro',
		'Compte de débit - libellé',
		'Compte de crédit - numéro',
		'Compte de crédit - libellé',
		'Moyen de paiement',
		'Numéro de chèque',
		'Numéro de pièce',
		'Remarques',
		'Projet'
	];

	protected function export($exercice)
	{
		return DB::getInstance()->iterate('SELECT
			journal.id,
			strftime(\'%d/%m/%Y\', date) AS date,
			(CASE cat.type WHEN 1 THEN \'Recette\' WHEN -1 THEN \'Dépense\' ELSE \'Autre\' END) AS type,
			(CASE cat.intitule WHEN NULL THEN \'\' ELSE cat.intitule END) AS cat,
			journal.libelle,
			montant,
			compte_debit,
			debit.libelle AS libelle_debit,
			compte_credit,
			credit.libelle AS libelle_credit,
			(CASE moyen_paiement WHEN NULL THEN \'\' ELSE moyen.nom END) AS moyen,
			numero_cheque,
			numero_piece,
			remarques,
			projet.libelle AS projet
			FROM compta_journal AS journal
				LEFT JOIN compta_categories AS cat ON cat.id = journal.id_categorie
				LEFT JOIN compta_comptes AS debit ON debit.id = journal.compte_debit
				LEFT JOIN compta_comptes AS credit ON credit.id = journal.compte_credit
				LEFT JOIN compta_moyens_paiement AS moyen ON moyen.code = journal.moyen_paiement
				LEFT JOIN compta_projets AS projet ON projet.id = journal.id_projet
			WHERE id_exercice = '.(int)$exercice.'
			ORDER BY journal.date;
		');
	}

	protected function exportName()
	{
		return sprintf('Export comptabilité - %s - %s', Config::getInstance()->get('nom_asso'), date('Y-m-d'));
	}

	public function toCSV($exercice)
	{
		return Utils::toCSV($this->exportName(), $this->export($exercice), $this->header);
	}

	public function toODS($exercice)
	{
		return Utils::toODS($this->exportName(), $this->export($exercice), $this->header);
	}

	public function fromCSV($path)
	{
		if (!file_exists($path) || !is_readable($path))
		{
			throw new \RuntimeException('Fichier inconnu : '.$path);
		}

		$fp = fopen($path, 'r');

		if (!$fp)
		{
			return false;
		}

		$db = DB::getInstance();
		$db->begin();
		$cats = new Categories;
		$journal = new Journal;

		$liste_cats = $db->getAssoc('SELECT intitule, id FROM compta_categories;');
		// Liste des moyens sous la forme nom -> code
		$liste_moyens = array_flip($cats->listMoyensPaiement(true));
		$liste_moyens = array_change_key_case($liste_moyens, \CASE_LOWER);

		// Liste associative des projets
		$liste_projets = $db->getAssoc('SELECT libelle, id FROM compta_projets;');

		$col = function($column) use (&$row, &$columns)
		{
			if (!isset($columns[$column]))
				return null;

			if (!isset($row[$columns[$column]]))
				return null;

			return $row[$columns[$column]];
		};

		$line = 0;
		$delim = Utils::find_csv_delim($fp);
		Utils::skip_bom($fp);

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, $delim);
			$line++;

			if (empty($row))
			{
				continue;
			}

			if ($line === 1)
			{
				if (trim($row[0]) != 'Numéro mouvement')
				{
					throw new UserException('Erreur sur la ligne ' . $line . ' : l\'entête des colonnes est absent ou incorrect.');
				}

				$columns = array_flip($row);

				continue;
			}

			if (count($row) != count($columns))
			{
				$db->rollback();
				throw new UserException('Erreur sur la ligne ' . $line . ' : le nombre de colonnes est incorrect.');
			}

			if (trim($row[0]) !== '' && !is_numeric($row[0]))
			{
				$db->rollback();
				throw new UserException('Erreur sur la ligne ' . $line . ' : la première colonne doit être vide ou contenir le numéro unique d\'opération.');
			}

			$id = $col('Numéro mouvement');
			$date = $col('Date');

			if (!preg_match('!^\d{2}/\d{2}/\d{4}$!', $date))
			{
				$db->rollback();
				throw new UserException('Erreur sur la ligne ' . $line . ' : la date n\'est pas au format jj/mm/aaaa.');
			}

			$date = explode('/', $date);
			$date = $date[2] . '-' . $date[1] . '-' . $date[0];

			// En dehors de l'exercice courant
			if ($db->test('compta_exercices', '(? < debut OR ? > fin) AND cloture = 0', $date, $date))
			{
				continue;
			}

			$debit = $col('Compte de débit - numéro');
			$credit = $col('Compte de crédit - numéro');

			$cat = $col('Catégorie');
			$moyen = strtolower($col('Moyen de paiement'));

			// Association du moyen de paiement par nom
			if ($moyen && array_key_exists($moyen, $liste_moyens))
			{
				$moyen = $liste_moyens[$moyen];
			}
			// Sinon on estime que c'est juste le code qui est fourni
			else
			{
				$moyen = substr(strtoupper($moyen), 0, 2);
			}

			// Vérification de l'existence du moyen de paiement
			// s'il n'est pas valide, on ne peut pas avoir de catégorie non plus
			if (!trim($moyen) || !in_array($moyen, $liste_moyens, true))
			{
				$moyen = false;
				$cat = false;
			}

			if ($cat && !array_key_exists($cat, $liste_cats))
			{
				$cat = $moyen = false;
			}

			$id_projet = null;

			if (!empty($col('Projet'))) {
				if (!array_key_exists($col('Projet'), $liste_projets)) {
					throw new UserException(sprintf('Erreur sur la ligne %d : le projet "%s" est inconnu', $line, $col('Projet')));
				}

				$id_projet = $liste_projets[$col('Projet')];
			}

			$data = [
				'libelle'       =>  $col('Libellé'),
				'montant'       =>  (float) $col('Montant'),
				'date'          =>  $date,
				'compte_credit' =>  $credit,
				'compte_debit'  =>  $debit,
				'numero_piece'  =>  $col('Numéro de pièce'),
				'remarques'     =>  $col('Remarques'),
				'id_projet'     =>  $id_projet,
			];

			if ($cat)
			{
				$data['moyen_paiement']	=	$moyen;
				$data['numero_cheque']	=	$col('Numéro de chèque');
				$data['id_categorie']	=	$liste_cats[$cat];
			}

			try {
				if (empty($id))
				{
					$journal->add($data);
				}
				else
				{
					$journal->edit($id, $data);
				}
			}
			catch (UserException $e)
			{
				throw new UserException(sprintf('Ligne %s: %s', $line, $e->getMessage()));
			}
		}

		$db->commit();

		fclose($fp);
		return true;
	}
}
