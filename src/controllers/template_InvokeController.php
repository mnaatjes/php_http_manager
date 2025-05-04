<?php
/*----------------------------------------------------------*/
/**
 * Template of the Invoke Controller
 */
/*----------------------------------------------------------*/
class TemplateInvokeController extends InvokeController {
    /*----------------------------------------------------------*/
    /**
     * POST Store Method
     * @param object $req HTTP Request Object
     * @param object $res HTTP Response Object
     * @param array $args Assoc Array of Arguments: params, etc. from Router()
     */
    /*----------------------------------------------------------*/
    protected function store(Request $req, Response $res, array $args=[]){}
    /*----------------------------------------------------------*/
    /**
     * GET Read Method for all records
     * @param object $req HTTP Request Object
     * @param object $res HTTP Response Object
     */
    /*----------------------------------------------------------*/
    protected function index(Request $req, Response $res){
        /**
         * Get all records
         */
        $records = $this->model->all();
        /**
         * Format response
         */
        $res->setHeader('status', 200);
        $res->setContentType('application/json');
        $res->setBody(json_encode($records));
        $res->send();
    }
    /*----------------------------------------------------------*/
    /**
     * GET Read Method for specific record(s)
     * @param object $req HTTP Request Object
     * @param object $res HTTP Response Object
     * @param array $args Assoc Array of Arguments: params, etc. from Router()
     */
    /*----------------------------------------------------------*/
    protected function show(Request $req, Response $res, array $args){}
    /*----------------------------------------------------------*/
    /**
     * PUT Read Method to update / edit a specific resource
     * @param object $req HTTP Request Object
     * @param object $res HTTP Response Object
     * @param array $args Assoc Array of Arguments: params, etc. from Router()
     */
    /*----------------------------------------------------------*/
    protected function edit(Request $req, Response $res, array $args){}
    /*----------------------------------------------------------*/
    /**
     * DELETE method to delete / destroy a specific record
     * @param object $req HTTP Request Object
     * @param object $res HTTP Response Object
     * @param array $args Assoc Array of Arguments: params, etc. from Router()
     */
    /*----------------------------------------------------------*/
    protected function destroy(Request $req, Response $res, array $args){}
    /*----------------------------------------------------------*/
    /**
     * Default: On Error condition
     * @param object $req HTTP Request Object
     * @param object $res HTTP Response Object
     */
    /*----------------------------------------------------------*/
    protected function onError(Request $req, Response $res){}
}
?>