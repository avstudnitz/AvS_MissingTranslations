<?php
/**
 * Translate model
 *
 * @author      Andreas von Studnitz <avs@avs-webentwicklung.de>
 */
class AvS_MissingTranslations_Model_Translate extends Mage_Core_Model_Translate
{
    /**
     * Return translated string from text.
     *
     * @param string $text
     * @param string $code
     * @return string
     */
    protected function _getTranslatedString($text, $code)
    {
        $translated = '';
        if (array_key_exists($code, $this->getData())) {
            $translated = $this->_data[$code];
        }
        elseif (array_key_exists($text, $this->getData())) {
            $translated = $this->_data[$text];
        }
        else {
            $translated = $text;
            $this->_logMissingTranslation($text, $code);
        }
        return $translated;
    }

    /**
     * Write missing translations to csv file
     *
     * @param string $text
     * @param string $code
     */
    protected function _logMissingTranslation($text, $code)
    {
        $area = Mage::getDesign()->getArea(); // frontend or adminhtml

        if (!Mage::getStoreConfigFlag('dev/missing_translations/' . $area)) return;

        $langCode = Mage::app()->getLocale()->getLocaleCode(); // i.e. de_DE

        $code = str_replace('"', '""', $code);
        $text = str_replace('"', '""', $text);

        try {
            $dirname = $this->_getDirName($area);

            $filename = $dirname . $langCode . '.csv';

            $file = fopen($filename, 'a+');

            // check if line already exists in file
            if ($this->_isInFile($file, $code)) {
                fclose($file);
                return;
            }

            // add new line
            fwrite($file, '"' . $code . '","' . $text . '"' . "\n");
            fclose($file);

        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Checks if $code string is already in file $file (first csv column)
     *
     * @param resource $file
     * @param string $code
     * @return bool
     */
    protected function _isInFile($file, $code)
    {
        while (($line = fgets($file)) !== false) {
            $translation = str_getcsv($line);
            if (is_array($translation) && isset($translation[0]) && $translation[0] == $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get directory where log files should be saved; create if necessary
     *
     * @param string $area
     * @return string
     */
    protected function _getDirName($area)
    {
        $dirname = Mage::getBaseDir('var') . DS . 'translation_log' . DS . $area . DS;
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        return $dirname;
    }
}
