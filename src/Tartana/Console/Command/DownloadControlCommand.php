<?php
namespace Tartana\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Tartana\Component\Command\Command;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class DownloadControlCommand extends \Symfony\Component\Console\Command\Command
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	private $repository = null;

	private $translator = null;

	public function __construct(DownloadRepository $repository, TranslatorInterface $translator)
	{
		parent::__construct('download:control');

		$this->repository = $repository;
		$this->translator = $translator;
	}

	protected function configure()
	{
		$this->setDescription('Manages the downloads!');

		$this->addArgument('action', InputArgument::OPTIONAL,
				'The action, can be: status, clearall, clearcompleted, clearfailed, resumefailed, resumeall or reprocess.', 'status');

		$this->addOption('destination', 'd', InputOption::VALUE_OPTIONAL,
				'The status and the other actions can take a destination option to show otartr modify only downloads with the given destination.');
		$this->addOption('compact', 'c', InputOption::VALUE_NONE, 'Shows a compact list of downloads for the status action.');
		$this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Shows the details of the download with the given id.', false);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Getting arguments
		$action = $input->getArgument('action');
		$destination = $input->getOption('destination');
		$compact = (boolean)$input->getOption('compact');
		$t = $this->translator;

		$command = null;
		/** @var Download[] $downloads **/
		$downloads = [];
		if (!empty($destination))
		{
			$downloads = $this->repository->findDownloadsByDestination($destination);
		}
		else
		{
			$downloads = $this->repository->findDownloads();
		}
		switch ($action)
		{
			case 'details':
				$id = $input->getOption('id');
				foreach ($downloads as $download)
				{
					if ($download->getId() != $id)
					{
						continue;
					}
					$sizes = [
							$t->trans('TARTANA_TEXT_SIZE_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_KILO_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_MEGA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_GIGA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_TERRA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_PETA_BYTE')
					];

					$table = new Table($output);

					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_ID'),
							$download->getId()
					]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_DESTINATION'),
							$download->getDestination()
					]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_FILE_NAME'),
							$download->getFileName()
					]);
					$table->addRow(
							[
									$t->trans('TARTANA_ENTITY_DOWNLOAD_STATE'),
									$t->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . $download->getState())
							]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_SIZE'),
							Util::readableSize($download->getSize(), $sizes)
					]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_PROGRESS'),
							$download->getProgress()
					]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_HASH'),
							$download->getHash()
					]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_LINK'),
							$download->getLink()
					]);
					$table->addRow([
							$t->trans('TARTANA_ENTITY_DOWNLOAD_MESSAGE'),
							$t->trans($download->getMessage())
					]);

					$table->render();
				}
				break;
			case 'status':
				if (!empty($downloads))
				{
					usort($downloads,
							function (Download $d1, Download $d2)
							{
								return strcmp($d1->getDestination(), $d2->getDestination());
							});
				}
				if ($compact)
				{
					$data = [];
					foreach ($downloads as $download)
					{
						$destination = $download->getDestination();
						if (!key_exists($destination, $data))
						{
							$data[$destination] = [
									'count' => 0,
									'size' => 0,
									'downloaded-size' => 0,
									'state' => [],
									'name' => ''
							];

							foreach (Download::$STATES_ALL as $state)
							{
								$data[$destination]['state'][$state] = 0;
							}
						}
						$data[$destination]['count'] ++;
						$data[$destination]['size'] += $download->getSize();
						if ($download->getState() != Download::STATE_DOWNLOADING_NOT_STARTED &&
								 $download->getState() != Download::STATE_DOWNLOADING_STARTED)
						{
							$data[$destination]['downloaded-size'] += $download->getSize();
						}

						$data[$destination]['state'][$download->getState()] ++;

						// Set the name when no one is set
						if (empty($data[$destination]['name']) && !empty($download->getFileName()))
						{
							$data[$destination]['name'] = $download->getFileName();
						}
					}

					$headers = [
							$t->trans('TARTANA_ENTITY_DOWNLOAD_DESTINATION'),
							$t->trans('TARTANA_TEXT_TOTAL'),
							$t->trans('TARTANA_COMMAND_DOWNLOAD_CONTROL_TOTAL_SIZE'),
							$t->trans('TARTANA_COMMAND_DOWNLOAD_CONTROL_DOWNLOADED_SIZE'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_FILE_NAME')
					];
					foreach (Download::$STATES_ALL as $state)
					{
						$headers[] = $t->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . $state);
					}

					$sizes = [
							$t->trans('TARTANA_TEXT_SIZE_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_KILO_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_MEGA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_GIGA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_TERRA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_PETA_BYTE')
					];
					foreach ($data as $destination => $content)
					{
						$output->writeln('');
						$output->writeln('<comment>' . $headers[0] . ': ' . $destination . '</comment>');

						$table = new Table($output);

						$table->addRow([
								$headers[1],
								$content['count']
						]);
						$table->addRow([
								$headers[2],
								Util::readableSize($content['size'], $sizes)
						]);
						$table->addRow([
								$headers[3],
								Util::readableSize($content['downloaded-size'], $sizes)
						]);
						$table->addRow([
								$headers[4],
								Util::shorten($content['name'], 30)
						]);

						foreach (Download::$STATES_ALL as $key => $state)
						{
							$table->addRow([
									$headers[$key + 5],
									$content['state'][$state]
							]);
						}
						$table->render();
					}
				}
				else
				{
					$headers = [
							$t->trans('TARTANA_ENTITY_DOWNLOAD_ID'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_PROGRESS'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_SIZE'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_STATE'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_FILE_NAME'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_LINK'),
							$t->trans('TARTANA_ENTITY_DOWNLOAD_MESSAGE')
					];
					$sizes = [
							$t->trans('TARTANA_TEXT_SIZE_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_KILO_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_MEGA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_GIGA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_TERRA_BYTE'),
							$t->trans('TARTANA_TEXT_SIZE_PETA_BYTE')
					];

					$lastDestinaton = null;
					$table = null;
					foreach ($downloads as $download)
					{
						if ($lastDestinaton != $download->getDestination())
						{
							if (!empty($table))
							{
								$table->render();
							}
							$table = new Table($output);
							$table->setHeaders($headers);
							$lastDestinaton = $download->getDestination();
							$output->writeln('');
							$output->writeln('<comment>' . $t->trans('TARTANA_ENTITY_DOWNLOAD_DESTINATION') . ': ' . $lastDestinaton . '</comment>');
						}
						$table->addRow(
								[
										$download->getId(),
										$download->getProgress(),
										Util::readableSize($download->getSize(), $sizes),
										$t->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . $download->getState()),
										Util::shorten($download->getFileName(), 30),
										Util::shorten($download->getLink(), 30),
										Util::shorten($t->trans($download->getMessage()), 30)
								]);
					}
					if (!empty($table))
					{
						$table->render();
					}
				}
				break;
			case 'clearall':
				$command = new DeleteDownloads($downloads);
				break;
			case 'clearcompleted':
				$toDelete = [];
				foreach ($downloads as $d)
				{
					if ($d->getState() == Download::STATE_PROCESSING_COMPLETED)
					{
						$toDelete[] = $d;
					}
				}
				$command = new DeleteDownloads($toDelete);
				break;
			case 'clearfailed':
				$toDelete = [];
				foreach ($downloads as $d)
				{
					if ($d->getState() == Download::STATE_DOWNLOADING_ERROR || $d->getState() == Download::STATE_PROCESSING_ERROR)
					{
						$toDelete[] = $d;
					}
				}
				$command = new DeleteDownloads($toDelete);
				break;
			case 'resumefailed':
				$command = new ChangeDownloadState($downloads,
						[
								Download::STATE_DOWNLOADING_ERROR,
								Download::STATE_PROCESSING_ERROR
						], Download::STATE_DOWNLOADING_NOT_STARTED);
				break;
			case 'resumeall':
				$command = new ChangeDownloadState($downloads,
						[
								Download::STATE_DOWNLOADING_STARTED,
								Download::STATE_DOWNLOADING_COMPLETED,
								Download::STATE_DOWNLOADING_ERROR,
								Download::STATE_PROCESSING_NOT_STARTED,
								Download::STATE_PROCESSING_STARTED,
								Download::STATE_PROCESSING_COMPLETED,
								Download::STATE_PROCESSING_ERROR
						], Download::STATE_DOWNLOADING_NOT_STARTED);
				break;
			case 'reprocess':
				$command = new ChangeDownloadState($downloads,
						[
								Download::STATE_PROCESSING_NOT_STARTED,
								Download::STATE_PROCESSING_STARTED,
								Download::STATE_PROCESSING_COMPLETED,
								Download::STATE_PROCESSING_ERROR
						], Download::STATE_DOWNLOADING_COMPLETED);
				break;
		}

		if ($command !== null)
		{
			$this->handleCommand($command);
			$output->writeln($t->trans('TARTANA_TEXT_COMMAND_RUN_SUCCESS'));
		}
		else if ($action != 'status' && $action != 'details')
		{
			$output->writeln($t->trans('TARTANA_COMMAND_DOWNLOAD_CONTROL_NO_ACTION_FOUND'));
		}
	}
}
