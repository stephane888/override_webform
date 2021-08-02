<?php
namespace Drupal\override_webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Stephane888\Debug\debugLog;
use Symfony\Component\HttpFoundation\Request;
use Drupal\webform_composite_test\Services\GenerateSummay;

/**
 * Returns responses for Override webform routes.
 */
class DiplayWebdormController extends ControllerBase implements ContainerInjectionInterface {

	/**
	 * The renderer service.
	 *
	 * @var \Drupal\Core\Render\RendererInterface
	 */
	protected $renderer;

	/**
	 * The webform request handler.
	 *
	 * @var \Drupal\webform\WebformRequestInterface
	 */
	protected $requestHandler;

	/**
	 * The webform token manager.
	 *
	 * @var \Drupal\webform\WebformTokenManagerInterface
	 */
	protected $tokenManager;

	/**
	 * The webform entity reference manager.
	 *
	 * @var \Drupal\webform\WebformEntityReferenceManagerInterface
	 */
	protected $webformEntityReferenceManager;

	private $headerTablesFields = [
		[
			'data' => '#id'
		],

		// [
		// 'data' => 'comment_avez_vous_connu_notre_site_internet_svp_'
		// ],
		[
			'data' => 'nom_du_projet'
		],
		[
			'data' => 'nom'
		],
		[
			'data' => 'prenom'
		],
		[
			'data' => 'numero_telephone'
		],
		[
			'data' => 'piece'
		],
		[
			'data' => 'status'
		],
		[
			'data' => 'note'
		],
		[
			'data' => 'dans_qutement_se_situe_votre_projet'
		]
	];

	private $RowsTables = [];

	/**
	 *
	 * @var \Drupal\webform_composite_test\Services\GenerateSummay
	 */
	protected $webformGeneratesummay;

	/**
	 *
	 * @var \Drupal\webform_composite_lists\Services\GenerateSummayList
	 */
	protected $GenerateSummayList;

	/**
	 *
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		$instance = parent::create($container);
		$instance->renderer = $container->get('renderer');
		$instance->requestHandler = $container->get('webform.request');
		$instance->tokenManager = $container->get('webform.token_manager');
		$instance->webformEntityReferenceManager = $container->get('webform.entity_reference_manager');
		$instance->webformGeneratesummay = $container->get('webform_composite_test.generatesummay');
		$instance->GenerateSummayList = $container->get('webform_composite_lists.generatesummaylist');
		return $instance;
	}

	private function listeWebforms() {
		$webform = \Drupal\webform\Entity\Webform::load('renovation');
		if ($webform->hasSubmissions()) {
			// dump('ha submission');
		}
		// dump($webform);
		$entityType = 'webform';
		$node_type = 'webform_submission';
		$query = \Drupal::entityQuery($entityType)->condition('type', $node_type);
		// charge les différents nodes
		$results = $query->execute();
		dump($results);
		$nodes = \Drupal::entityTypeManager()->getStorage($entityType)->loadMultiple($results);
		// compte le nombre de resultat
		// $nombre = $query->count()->execute();
		dump($nodes);
	}

	/**
	 * Builds the response.
	 */
	public function build(Request $Request, WebformInterface $webform = NULL) {
		// return $this->exampleSortable();
		return $this->displayTableWebform($webform);

		// Recupere l'enssemble des champs qui ont permit de construire le formulaire.
		// $formulaire = $webform->getElementsInitialized();
		// pour retourner le tableu qui a permit de construire un chmaps
		// $webform->getElement('page_1');//$webform->getElementsAttachments()
		// dump($webform);
		// retourne les differentes pages utiliser au niveau du formulaire.
		// dump($webform->getPages());
		// ***
		// $current_route_name = \Drupal::service('current_route_match');
		// dump($current_route_name->getParameters());
		// **
		// get information about current page(route)
		// dump();
		// debugLog::$themeName = null;
		// debugLog::kintDebugDrupal($Request, 'Request', null, true);
	}

	private function displayTableWebform(WebformInterface $webform) {
		// dump($webform->getElement('piece'));
		$id_webform = 'renovation';
		$build = [];
		if ($webform->hasSubmissions()) {
			$query = \Drupal::entityQuery('webform_submission')->condition('webform_id', $id_webform)
				->sort('created', 'DESC')
				->pager(6);
			// $query->tableSort($this->headerTablesFields);
			$result = $query->execute();
			$submission_data = [];
			foreach ($result as $item) {
				$submission = \Drupal\webform\Entity\WebformSubmission::load($item);
				$submission_data[$item] = $submission->getData();
			}

			// debugLog::$themeName = null;
			// debugLog::kintDebugDrupal($submission_data, 'Webform_submission_renovation', null, true);
			// dump($submission_data);
			$this->getRows($submission_data, $webform);
			$build['table'] = [
				'#type' => 'table',
				'#header' => $this->getLabelHeader($webform),
				'#rows' => $this->RowsTables,
				'#empty' => $this->t('Aucun devis disponible'),
				'#attributes' => [
					'class' => [
						'table',
						'table-striped',
						'table-md',
						'table-bordered'
					]
				]
			];

			$build['pager'] = [
				'#type' => 'pager'
			];
			$build['#attached']['library'][] = 'override_webform/override_webform';
		}
		return $build;
	}

	private function getLabelHeader(WebformInterface $webform) {
		$headers = $this->headerTablesFields;
		foreach ($headers as $key => $header) {
			if (isset($header['data'])) {
				$element = $webform->getElement($header['data']);
				if (! empty($element['#title']))
					$headers[$key]['data'] = $element['#title'];
			}
		}
		// dump($headers);
		return $headers;
	}

	private function exampleSortable() {
		$header = [
			'id' => [
				'data' => $this->t('ID'),
				'specifier' => 'nid'
			],
			'title' => [
				'data' => $this->t('Title'),
				'specifier' => 'title'
			],
			'created' => [
				'data' => $this->t('Created'),
				'specifier' => 'created',
				// Set default sort criteria.
				'sort' => 'desc'
			],
			'uid' => [
				'data' => $this->t('Author'),
				'specifier' => 'uid'
			]
		];

		$storage = \Drupal::entityTypeManager()->getStorage('node');

		$query = $storage->getQuery();
		$query->condition('status', \Drupal\node\NodeInterface::PUBLISHED);
		$query->condition('type', 'domaine');
		$query->tableSort($header);
		// Default value is 10.
		$query->pager(3);
		$nids = $query->execute();

		$date_formatter = \Drupal::service('date.formatter');
		$rows = [];
		foreach ($storage->loadMultiple($nids) as $node) {
			$row = [];
			$row[] = $node->id();
			$row[] = $node->toLink();
			$created = $node->get('created')->value;
			$row[] = [
				'data' => [
					'#theme' => 'time',
					'#text' => $date_formatter->format($created),
					'#attributes' => [
						'datetime' => $date_formatter->format($created, 'custom', \DateTime::RFC3339)
					]
				]
			];
			$row[] = [
				'data' => $node->get('uid')->view()
			];
			$rows[] = $row;
		}

		$build['table'] = [
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $rows,
			'#empty' => $this->t('Aucun devis disponible')
		];

		$build['pager'] = [
			'#type' => 'pager'
		];
		return $build;
	}

	private function getRows(array $submission_data, WebformInterface $webform) {
		$this->RowsTables = [];
		foreach ($submission_data as $key => $row) {
			$this->RowsTables[$key] = [];
			foreach ($this->headerTablesFields as $cell => $header) {
				if (isset($header['data'])) {
					if ($header['data'] == "#id") {
						$this->RowsTables[$key][] = [
							'data' => $key
						];
					}
					elseif (isset($row[$header['data']])) {
						if (is_array($row[$header['data']])) {
							if ($header['data'] == 'piece') {
								// dump($webform->getElement($header['data']));
								$this->RowsTables[$key][$cell]['data'] = [];
								$this->webformGeneratesummay->submission = $row[$header['data']];
								$this->webformGeneratesummay->getTemplatesTablesPrices($webform->getElement($header['data']), $this->RowsTables[$key][$cell]['data']);
							}
							elseif ($header['data'] == 'dans_qutement_se_situe_votre_projet') {
								$this->RowsTables[$key][$cell]['data'] = [];
								$this->GenerateSummayList->submission = $row[$header['data']];
								$this->GenerateSummayList->getTemplates($webform->getElement($header['data']), $this->RowsTables[$key][$cell]['data']);
							}
							else {
								$this->RowsTables[$key][$cell]['data'] = [];
								$this->BuildSubArray($row[$header['data']], $this->RowsTables[$key][$cell]['data']);
							}
						}
						else {
							$this->RowsTables[$key][$cell] = [
								'data' => $row[$header['data']]
							];
							// dump($this->RowsTables[$key][$cell]);
						}
					}
					else {
						$this->RowsTables[$key][$cell] = [
							'data' => ''
						];
					}
				}
			}
		}
		// dump($this->RowsTables);
	}

	private function BuildSubArray(array $submission_data, array &$build) {
		// dump($submission_data);
		// return;
		$headers = [];
		foreach (array_keys($submission_data) as $cell) {
			$headers[] = [
				'data' => $cell
			];
		}
		$rows = [];
		$row = [];
		foreach ($submission_data as $cell => $tr_data) {
			if (is_array($tr_data)) {
				$row[] = [
					'data' => 'Array'
				];
			}
			else {
				$row[] = [
					'data' => $tr_data
				];
			}
		}
		$rows[] = $row;
		$build['table'] = [
			'#type' => 'table',
			'#header' => $headers,
			'#rows' => $rows,
			'#empty' => $this->t('Vide')
		];
	}
}
