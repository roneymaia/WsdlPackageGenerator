<?php

namespace WsdlToPhp\PackageGenerator\File;

use WsdlToPhp\PackageGenerator\Model\Struct as StructModel;
use WsdlToPhp\PackageGenerator\Model\Method as MethodModel;
use WsdlToPhp\PackageGenerator\Model\Service as ServiceModel;
use WsdlToPhp\PhpGenerator\Element\PhpAnnotation;
use WsdlToPhp\PhpGenerator\Element\PhpAnnotationBlock;
use WsdlToPhp\PackageGenerator\Generator\Generator;

class Tutorial extends AbstractFile
{
    const FILE_NAME = 'howtos';
    /**
     * @param Generator $generator
     * @param string $name
     * @param string $destination
     */
    public function __construct(Generator $generator, $name, $destination)
    {
        parent::__construct($generator, self::FILE_NAME, $destination);
    }
    /**
     * @see \WsdlToPhp\PackageGenerator\File\AbstractFile::beforeWrite()
     */
    public function beforeWrite()
    {
        parent::beforeWrite();
        $this
            ->defineNamespace()
            ->addMainAnnotationBlock()
            ->addAutoload()
            ->addOptionsAnnotationBlock()
            ->addOptions()
            ->addContent();
    }
    /**
     * @return Tutorial
     */
    public function defineNamespace()
    {
        $this->getFile()->setNamespace(Generator::getPackageName());
        return $this;
    }
    /**
     * @return Tutorial
     */
    public function addMainAnnotationBlock()
    {
        $this->getFile()->addAnnotationBlockElement($this->getAnnotationBlock());
        return $this;
    }
    /**
     * @return PhpAnnotationBlock
     */
    protected function getAnnotationBlock()
    {
        $block = new PhpAnnotationBlock();
        $this
            ->addChild($block, 'This file aims to show you how to use this generated package.')
            ->addChild($block, 'In addition, the goal is to show which methods are available and the fist needed parameter(s)')
            ->addChild($block, 'You have to use an associative array such as:')
            ->addChild($block, '- the key must be a constant beginning with WSDL_ from AbstractSoapClientbase class each generated ServiceType class extends this class')
            ->addChild($block, '- the value must be the corresponding key value (each option matches a {@link http://www.php.net/manual/en/soapclient.soapclient.php} option)')
            ->addChild($block, '$options = array(')
            ->addChild($block, sprintf('AbstractSoapClientBase::WSDL_URL => \'%s\',', $this->getGenerator()->getWsdl(0)->getName()))
            ->addChild($block, 'AbstractSoapClientBase::WSDL_TRACE => true,')
            ->addChild($block, 'AbstractSoapClientBase::WSDL_LOGIN => \'you_secret_login\',')
            ->addChild($block, 'AbstractSoapClientBase::WSDL_PASSWORD => \'you_secret_password\',')
            ->addChild($block, ');')
            ->addChild($block, 'etc....')
            ->addChild($block, 'Then instantiate the ServiceType class such as:')
            ->addChild($block, '- $wsdlObject = new PackageNameWsdlClass($wsdl)');
        return $block;
    }
    /**
     * @param PhpAnnotationBlock $block
     * @param string $content
     * @return Tutorial
     */
    public function addChild(PhpAnnotationBlock $block, $content)
    {
        $block->addChild(new PhpAnnotation(PhpAnnotation::NO_NAME, $content, AbstractModelFile::ANNOTATION_LONG_LENGTH));
        return $this;
    }
    /**
     * @return Tutorial
     */
    public function addAutoload()
    {
        $this
            ->getFile()
            ->getMainElement()
            ->addChild(sprintf('require_once __DIR__ . \'/vendor/autoload.php\';'));
        return $this;
    }
    /**
     * @return Tutorial
     */
    public function addContent()
    {
        foreach ($this->getGenerator()->getServices() as $service) {
            $this
                ->addAnnotationBlockFromService($service)
                ->addContentFromService($service);
        }
        return $this;
    }
    /**
     * @return Tutorial
     */
    protected function addOptionsAnnotationBlock()
    {
        $this->addAnnotationBlock(array(
            'Minimal options',
        ));
        return $this;
    }
    /**
     * @return Tutorial
     */
    protected function addOptions()
    {
        $this
            ->getFile()
            ->getMainElement()
            ->addChild('$options = array(')
                ->addChild($this->getFile()->getMainElement()->getIndentedString(sprintf('AbstractSoapClientBase::WSDL_URL => \'%s\',', $this->getGenerator()->getWsdl(0)->getName()), 1))
            ->addChild(');');
        return $this;
    }
    /**
     * @param ServiceModel $service
     * @return Tutorial
     */
    protected function addAnnotationBlockFromService(ServiceModel $service)
    {
        return $this->addAnnotationBlock(array(
            sprintf('Samples for %s ServiceType', $service->getName()),
        ));
    }
    /**
     * @param Service $service
     * @return Tutorial
     */
    protected function addContentFromService(ServiceModel $service)
    {
        foreach ($service->getMethods() as $method) {
            $serviceVariableName = lcfirst($service->getName());
            $this
                ->addServiceDeclaration($serviceVariableName, $service)
                ->addAnnotationBlockFromMethod($method)
                ->addContentFromMethod($serviceVariableName, $method);
        }
        return $this;
    }
    /**
     * @param string $serviceVariableName
     * @param ServiceModel $service
     * @return Tutorial
     */
    protected function addServiceDeclaration($serviceVariableName, ServiceModel $service)
    {
        $this
            ->getFile()
            ->getMainElement()
            ->addChild(sprintf('$%s = new %s();', $serviceVariableName, $service->getName()));
        return $this;
    }
    /**
     * @param MethodModel $method
     * @return Tutorial
     */
    protected function addAnnotationBlockFromMethod(MethodModel $method)
    {
        return $this->addAnnotationBlock(array(
            sprintf('Sample call for %s operation/method', $method->getName()),
        ));
    }
    /**
     * @param string $serviceVariableName
     * @param MethodModel $method
     * @return Tutorial
     */
    protected function addContentFromMethod($serviceVariableName, MethodModel $method)
    {
        $this
            ->getFile()
            ->getMainElement()
            ->addChild(sprintf('if ($%s->%s(%s) !== false) {', $serviceVariableName, $method->getName(), $this->getMethodParameters($method)))
                ->addChild($this->getFile()->getMainElement()->getIndentedString(sprintf('print_r($%s->getResult());', $serviceVariableName), 1))
            ->addChild('} else {')
                ->addChild($this->getFile()->getMainElement()->getIndentedString(sprintf('print_r($%s->getLastError());', $serviceVariableName), 1))
            ->addChild('}');
        return $this;
    }
    /**
     * @param MethodModel $method
     * @return string
     */
    protected function getMethodParameters(MethodModel $method)
    {
        $parameters = array();
        if (is_array($method->getParameterType())) {
            foreach ($method->getParameterType() as $parameterName => $parameterType) {
                $parameters[] = $this->getMethodParameter($parameterType);
            }
        } else {
            $parameters[] = $this->getMethodParameter($method->getParameterType());
        }
        return implode(', ', $parameters);
    }
    /**
     * @param string $parameterType
     * @return string
     */
    protected function getMethodParameter($parameterType)
    {
        $parameter = $parameterType;
        $model = $this->getGenerator()->getStruct($parameterType);
        if ($model instanceof StructModel && $model->getIsStruct() && !$model->getIsRestriction()) {
            $parameter = sprintf('new %s()', $model->getPackagedName(true));
        }
        return $parameter;
    }
    /**
     * @param string[]|PhpAnnotation[] $content
     * @return Tutorial
     */
    protected function addAnnotationBlock($content)
    {
        $this
            ->getFile()
            ->getMainElement()
            ->addChild(new PhpAnnotationBlock($content));
        return $this;
    }
}
