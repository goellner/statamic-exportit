<?php

namespace Statamic\Addons\Exportit;

use Statamic\Extend\Controller;
use Statamic\API\Collection;
use Statamic\API\Fieldset;
use Statamic\API\Entry;
use SplTempFileObject;
use League\Csv\Writer;
use Statamic\API\File;
use Illuminate\Http\Response;

class ExportitController extends Controller
{
    private $writer;
    private $csv_header;
    /**
     * Maps to your route definition in routes.yaml
     *
     * @return mixed
     */
    public function index() {


        return $this->view('index');
    }

    public function exportdata() {
        $handle = $_POST['selectedcollection'];

        $this->writer = Writer::createFromFileObject(new SplTempFileObject);

        $this->insertHeaders($handle);
        $this->insertData($handle);

        // TODO: Don't save to storage, start immediate download instead.
        $document = File::disk('storage');
        $document->put('exportit.csv', $this->writer);

        $data = [
            'csv' => $this->writer
        ];
        return $this->view('exportdata');


    }

    public function download() {
        $document = File::disk('storage');
        $file = $document->get('exportit.csv');
        return response($file)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="exportit.csv"');
    }

    // Creates and inserts CSV Header based on collection fieldset
    private function insertHeaders($handle)
    {
        // Fetch collection fieldset
        $collection = Collection::whereHandle($handle);
        $collection_fieldset = $collection->get('fieldset');
        $fieldset = Fieldset::get($collection_fieldset);
        $fieldset_content = $fieldset->fields();
        $header_data = array_keys($fieldset_content);

        // Adding title field, since it is not defined in fieldset
        array_unshift($header_data, 'title' );

        $this->csv_header = $header_data;

        $this->writer->insertOne($header_data);
    }

    // Creates content based on fieldset fields
    private function insertData($handle)
    {
        $header_data = $this->csv_header;

        $collectiondata = Entry::whereCollection($handle);

        $data = collect($collectiondata)->map(function ($entry) use ($header_data) {

            $ret = array();
            $entry = $entry->toArray();

            foreach ($header_data as $key => $value) {
                if(array_key_exists($value, $entry)) {
                    // convert entry array to pipe delimited string
                    $entry_value = '';
                    if(is_array($entry[$value])) {
                        $entry_value = implode('|', $entry[$value]);
                    } else {
                        $entry_value = $entry[$value];
                    }

                    $ret[] = $entry_value;
                }
                else {
                    $ret[] = '';
                }
            }
            return $ret;
        })->toArray();

        $this->writer->insertAll($data);

    }
}
