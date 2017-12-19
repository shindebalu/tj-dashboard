<?php
/**
 * @package    Com_Tjdashboard
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  2017 Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('plugins');

/**
 * Form Field class for the Joomla Framework.
 *
 * @since  11.4
 */
class JFormFieldTjdashboardSourcePlugins extends JFormFieldPlugins
{
	/**
	 * The field type.
	 *
	 * @var    string
	 * @since  11.4
	 */
	protected $type = 'tjdashboardsourceplugins';

	/**
	 * The path to folder for plugins.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $folder;

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'folder':
				return $this->folder;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'folder':
				$this->folder = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->folder = (string) $this->element['folder'];
		}

		return $return;
	}

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array  An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$folder        = $this->folder;

		if (!empty($folder))
		{
			// Get list of plugins
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('element AS value, name AS text')
				->from('#__extensions')
				->where('folder = ' . $db->quote($folder))
				->where('enabled = 1')
				->order('ordering, name');

			$options   = $db->setQuery($query)->loadObjectList();
			$lang      = JFactory::getLanguage();

			$tjDashboardSourcePlugins = array();
			$j = 0;

			foreach ($options as $i => $item)
			{
				$source    = JPATH_PLUGINS . '/' . $folder . '/' . $item->value . '/' . $item->value;
				$extension = 'plg_' . $folder . '_' . $item->value;
				$lang->load($extension . '.sys', JPATH_ADMINISTRATOR, null, false, true) || $lang->load($extension . '.sys', $source, null, false, true);

				// @Todo : Need to improve this code
				$dataSources = array_diff(scandir($source), array('..', '.'));

				foreach ($dataSources as $dataSourceFile)
				{
					$j++;
					$className = ucfirst($item->value) . ucfirst(str_replace('.php', '', $dataSourceFile)) . 'Datasource';
					require_once $source . '/' . $dataSourceFile;
					$dataSourceClassObject = new $className;
					$dataSourceName 	 = $item->text . ' ' . $dataSourceClassObject->dataSourceName;
					$dataSourceNameValue = strtolower(trim($item->text)) . '.' . strtolower(str_replace(' ', '', $dataSourceClassObject->dataSourceName));

					$tjDashboardSourcePlugins[$j]->text   = $dataSourceName;
					$tjDashboardSourcePlugins[$j]->value  = $dataSourceNameValue;
				}
			}
		}
		else
		{
			JLog::add(JText::_('JFRAMEWORK_FORM_FIELDS_PLUGINS_ERROR_FOLDER_EMPTY'), JLog::WARNING, 'jerror');
		}

		return array_merge($tjDashboardSourcePlugins);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$html = parent::getInput();

		// @TODO : Convert this into a snippet that loads only once

		$doc = JFactory::getDocument();
		$data = 'morris.donot,morris.bar';
		$supportedRenderers = 'var supportedRenderer = "' . $data . '"';
		$doc->addScriptDeclaration($supportedRenderers, 'text/javascript');
		$doc->addScriptDeclaration('
			jQuery(document).ready(function() {
				jQuery("#' . $this->id . '").on("change", function() {
						list = jQuery("#jform_renderer_plugin");
						var supportedRendererArray = supportedRenderer.split(",");
						jQuery.each(supportedRendererArray, function(index, item) {
							list.append(new Option(item,item));
						});
					});
				});
			');

		return $html;
	}
}
