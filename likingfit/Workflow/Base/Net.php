<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/16
 * Time: 上午11:55
 */
namespace likingfit\Workflow\Base;
class Net extends Object {
    /**  @var Synchronizer */
    private $start;
    /**  @var Synchronizer[] */
    private $ends;
    private $elementMap = array();
    
    /**
     * @param $elementId
     *
     * @return Element
     */
    public function getElement($elementId){
        return $this->elementMap[$elementId];
    }
    public function addElement(Element $element){
        $this->elementMap[$element->getId()] = $element;
    }
    
    public function getStart() {
        return $this->start;
    }
    
    public function setStart($startNode) {
        $this->start = $startNode;
    }
    
    public function getEnds() {
        return $this->ends;
    }
    
    public function setEnds($endNodes) {
        $this->ends = $endNodes;
    }
	/**
     * @return array $elementMap
     */
    public function getElementMap() {
        return $this->elementMap;
    }

	/**
     * @param multitype: $elementMap
     */
    public function setElementMap($elementMap) {
        $this->elementMap = $elementMap;
    }


}