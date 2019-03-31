<?php

namespace Drupal\custom_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Custom layout class.
 */
class CustomLayout extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The custom class vocabulary, used for a better UX for editors.
   *
   * Editors will select a user-friendly term name when adding the layout, and
   * behind the scenes that term contains a field with the list of actual
   * classes that will be added to the layout.
   *
   * @var string
   *   The machine name of the class vocabulary.
   */
  protected $classVid;

  /**
   * The custom title class vocabulary, used for a better UX for editors.
   *
   * Editors will select a user-friendly term name when adding the layout, and
   * behind the scenes that term contains a field with the list of actual
   * classes that will be added to the layout.
   *
   * @var string
   *   The machine name of the title class vocabulary.
   */
  protected $titleClassVid;

  /**
   * The field on the vocabulary term that contains actual classes.
   *
   * @var string
   *   The machine name of the vocabulary class field.
   */
  protected $classField;

  /**
   * Entity Type Manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Assumption is that this vocabulary exists, and has field_class field.
    $this->classVid = 'classes';
    $this->titleClassVid = 'title_classes';
    $this->classField = 'field_class';
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * The custom class vocabulary.
   */
  public function getVid() {
    return $this->classVid;
  }

  /**
   * The custom class vocabulary.
   */
  public function getTitleVid() {
    return $this->titleClassVid;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'extra_classes' => '',
      'terms' => '',
      'title' => '',
      'title_element' => '',
      'title_terms' => '',
      'title_classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    // Allow editors to select a title for the section.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $configuration['title'],
      '#description' => $this->t('Custom title for this section.'),
    ];
    $options = [
      'span' => $this->t('Span'),
      'div' => $this->t('Div'),
      'h1' => $this->t('Heading 1'),
      'h2' => $this->t('Heading 2'),
      'h3' => $this->t('Heading 3'),
      'h4' => $this->t('Heading 4'),
    ];
    $form['title_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Title element'),
      '#default_value' => !empty($configuration['title_element']) ? $configuration['title_element'] : 'h2',
      '#options' => $options,
      '#description' => $this->t('Element for the custom title.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[title]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($this->getTitleVid());
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['title_terms'] = [
      '#type' => 'select',
      '#title' => $this->t('Title classes'),
      '#default_value' => $configuration['title_terms'],
      '#options' => $options,
      '#description' => $this->t('Select classes for the title.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[title]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // Allow editors to select html classes using user-friendly term names.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($this->getVid());
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['terms'] = [
      '#type' => 'select',
      '#title' => $this->t('Classes'),
      '#default_value' => $configuration['terms'],
      '#options' => $options,
      '#description' => $this->t('Wrap the markup for this section with one or more classes.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Any additional form validation that is required.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['title_element'] = $form_state->getValue('title_element');
    $this->configuration['title_terms'] = $form_state->getValue('title_terms');
    $this->configuration['title_classes'] = $form_state->getValue('title_classes');
    $this->configuration['terms'] = $form_state->getValue('terms');
    $this->configuration['extra_classes'] = $form_state->getValue('extra_classes');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {

    // Build the render array as usual.
    $build = parent::build($regions);

    // Retrieve the vocabulary term info.
    $configuration = $this->getConfiguration();
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Add vocabulary classes to section title.
    $more_classes = [];
    $terms = (array) $configuration['title_terms'];
    foreach ($terms as $term_id) {
      if ($term = $storage->load($term_id)) {
        $value = $term->{$this->classField}->value;
        $more_classes[] = $value;
      }
    }
    if (!empty($more_classes)) {
      $settings = $build['#settings'];
      $settings['title_classes'] = implode(' ', $more_classes);
      $build['#settings'] = $settings;
    }

    // Add vocabulary classes to any other classes.
    $more_classes = [];
    $terms = (array) $configuration['terms'];
    foreach ($terms as $term_id) {
      if ($term = $storage->load($term_id)) {
        $value = $term->{$this->classField}->value;
        $more_classes[] = $value;
      }
    }
    if (!empty($more_classes)) {
      $settings = $build['#settings'];
      $settings['extra_classes'] = implode(' ', $more_classes);
      $build['#settings'] = $settings;
    }

    return $build;
  }

}
