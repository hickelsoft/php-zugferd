<?php

namespace horstoeko\zugferd;

use horstoeko\zugferd\ZugferdObjectHelper;

class ZugferdDocumentBuilder extends ZugferdDocument
{
    /**
     * Object Helper
     *
     * @var ZugferdObjectHelper
     */
    protected $objectHelper = null;

    /**
     * HeaderTradeAgreement
     *
     * @var object
     */
    protected $headerTradeAgreement = null;

    /**
     * HeaderTradeDelivery
     *
     * @var object
     */
    protected $headerTradeDelivery = null;

    /**
     * HeaderTradeSettlement
     *
     * @var object
     */
    protected $headerTradeSettlement = null;

    /**
     * Constructor
     */
    public function __construct(int $profile)
    {
        parent::__construct($profile);

        $this->objectHelper = new ZugferdObjectHelper($profile);

        $this->InitNewDocument();
    }

    /**
     * Creates a new ZugferdDocumentBuilder with profile $profile
     *
     * @param integer $profile
     * @return ZugferdDocumentBuilder
     */
    public static function CreateNew(int $profile): ZugferdDocumentBuilder
    {
        return (new self($profile))->InitNewDocument();
    }

    /**
     * Initialized a new document with profile settings
     *
     * @return ZugferdDocumentBuilder
     */
    public function InitNewDocument(): ZugferdDocumentBuilder
    {
        $this->invoiceObject = $this->objectHelper->GetCrossIndustryInvoice();
        $this->headerTradeAgreement = $this->invoiceObject->getSupplyChainTradeTransaction()->getApplicableHeaderTradeAgreement();
        $this->headerTradeDelivery = $this->invoiceObject->getSupplyChainTradeTransaction()->getApplicableHeaderTradeDelivery();
        $this->headerTradeSettlement = $this->invoiceObject->getSupplyChainTradeTransaction()->getApplicableHeaderTradeSettlement();
        return $this;
    }

    /**
     * Set main information about this document
     *
     * @param string $documentno
     * @param string $documenttypecode
     * @param \DateTime $documentdate
     * @param string $invoiceCurrency
     * @param string|null $documentname
     * @param string|null $documentlanguage
     * @param \DateTime|null $effectiveSpecifiedPeriod
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentInformation(string $documentno, string $documenttypecode, \DateTime $documentdate, \DateTime $duedate, string $invoiceCurrency, ?string $documentname = null, ?string $documentlanguage = null, ?\DateTime $effectiveSpecifiedPeriod = null): ZugferdDocumentBuilder
    {
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "setID", $this->objectHelper->GetIdType($documentno));
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "setName", $this->objectHelper->GetTextType($documentname));
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "setTypeCode", $this->objectHelper->GetCodeType($documenttypecode));
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "setIssueDateTime", $this->objectHelper->GetDateTimeType($documentdate));
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "addToLanguageID", $this->objectHelper->GetIdType($documentlanguage));
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "setEffectiveSpecifiedPeriod", $this->objectHelper->GetSpecifiedPeriodType(null, null, $effectiveSpecifiedPeriod, null));

        $this->objectHelper->TryCall($this->headerTradeSettlement, "setInvoiceCurrencyCode", $this->objectHelper->GetIdType($invoiceCurrency));

        $this->AddDocumentPaymentTerms(null, $duedate);
        
        return $this;
    }

    /**
     * Set general payment information
     *
     * @param string|null $creditorReferenceID
     * @param string|null $paymentReference
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentGeneralPaymentInformation(?string $creditorReferenceID = null, ?string $paymentReference = null): ZugferdDocumentBuilder
    {
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setCreditorReferenceID", $this->objectHelper->GetIdType($creditorReferenceID));
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setPaymentReference", $this->objectHelper->GetIdType($paymentReference));
        return $this;
    }

    /**
     * Mark document as a copy from the original one
     *
     * @return ZugferdDocumentBuilder
     */
    public function SetIsDocumentCopy(): ZugferdDocumentBuilder
    {
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "setCopyIndicator", $this->objectHelper->GetIndicatorType(true));
        return $this;
    }

    /**
     * Mark document as a test document
     *
     * @return ZugferdDocumentBuilder
     */
    public function SetIsTestDocument(): ZugferdDocumentBuilder
    {
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocumentContext(), "setTestIndicator", $this->objectHelper->GetIndicatorType(true));
        return $this;
    }

    /**
     * Document money summation
     *
     * @param float $grandTotalAmount
     * @param float $duePayableAmount
     * @param float|null $lineTotalAmount
     * @param float|null $chargeTotalAmount
     * @param float|null $allowanceTotalAmount
     * @param float|null $taxBasisTotalAmount
     * @param float|null $taxTotalAmount
     * @param float|null $roundingAmount
     * @param float|null $totalPrepaidAmount
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentSummation(float $grandTotalAmount, float $duePayableAmount, ?float $lineTotalAmount = null, ?float $chargeTotalAmount = null, ?float $allowanceTotalAmount = null, ?float $taxBasisTotalAmount = null, ?float $taxTotalAmount = null, ?float $roundingAmount = null, ?float $totalPrepaidAmount = null): ZugferdDocumentBuilder
    {
        $summation = $this->objectHelper->GetTradeSettlementHeaderMonetarySummationType($grandTotalAmount, $duePayableAmount, $lineTotalAmount, $chargeTotalAmount, $allowanceTotalAmount, $taxBasisTotalAmount, $taxTotalAmount, $roundingAmount, $totalPrepaidAmount);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setSpecifiedTradeSettlementHeaderMonetarySummation", $summation);
        return $this;
    }

    /**
     * Add a note to the docuzment
     *
     * @param string $content
     * @param string|null $contentCode
     * @param string|null $subjectCode
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentNote(string $content, ?string $contentCode = null, ?string $subjectCode = null): ZugferdDocumentBuilder
    {
        $note = $this->objectHelper->GetNoteType($content, $contentCode, $subjectCode);
        $this->objectHelper->TryCall($this->invoiceObject->getExchangedDocument(), "addToIncludedNote", $note);
        return $this;
    }

    /**
     * Seller
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentSeller(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setSellerTradeParty", $sellerTradeParty);
        return $this;
    }

    /**
     * Buyer
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @param string|null $buyerrefno
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentBuyer(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null, ?string $buyerrefno): ZugferdDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $buyerReference = $this->objectHelper->GetCodeType($buyerrefno);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setBuyerTradeParty", $buyerTradeParty);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setBuyerReference", $buyerReference);
        return $this;
    }

    /**
     * Tax agent of the seller
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetSellerTaxRepresentativeTradeParty(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $sellerTaxRepresentativeTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setSellerTaxRepresentativeTradeParty", $sellerTaxRepresentativeTradeParty);
        return $this;
    }

    /**
     * Detailed information on the deviating Consumer
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetProductEndUserTradeParty(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $productEndUserTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setProductEndUserTradeParty", $productEndUserTradeParty);
        return $this;
    }

    /**
     * Ship-To
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentShipTo(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setShipToTradeParty", $shipToTradeParty);
        return $this;
    }

    /**
     * Detailed information on the different end recipient
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentUltimateShipTo(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setUltimateShipToTradeParty", $shipToTradeParty);
        return $this;
    }

    /**
     * Detailed information on the different end recipient
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentShipFrom(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setShipFromTradeParty", $shipToTradeParty);
        return $this;
    }

    /**
     * Invoicer (Rechnungssteller)
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentInvoicer(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $invoicerTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setInvoicerTradeParty", $invoicerTradeParty);
        return $this;
    }

    /**
     * Invoicee,
     * Detailed information on the different invoice recipient,
     * Detailinformationen zum abweichenden Rechnungsempfänger
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentInvoicee(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setInvoiceeTradeParty", $invoiceeTradeParty);
        return $this;
    }

    /**
     * Payee,
     * Detailed information on the different invoice payee,
     * Zahlungsempfänger
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $globalID
     * @param string|null $globalIDType
     * @param string|null $description
     * @param string|null $lineone
     * @param string|null $linetwo
     * @param string|null $linethree
     * @param string|null $postcode
     * @param string|null $city
     * @param string|null $country
     * @param string|null $subdivision
     * @param string|null $legalorgid
     * @param string|null $legalorgtype
     * @param string|null $legalorgname
     * @param string|null $contactpersonname
     * @param string|null $contactdepartmentname
     * @param string|null $contactphoneno
     * @param string|null $contactfaxno
     * @param string|null $contactemailaddr
     * @param string|null $taxregtype
     * @param string|null $taxregid
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentPayee(string $name, ?string $id = null, ?string $globalID = null, ?string $globalIDType = null, ?string $description = null, ?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null, ?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null, ?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailaddr = null, ?string $taxregtype = null, ?string $taxregid = null): ZugferdDocumentBuilder
    {
        $payeeTradeParty = $this->objectHelper->GetTradeParty($id, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setPayeeTradeParty", $payeeTradeParty);
        return $this;
    }

    /**
     * Set the delivery terms
     *
     * @param string|null $code
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentDeliveryTerms(?string $code): ZugferdDocumentBuilder
    {
        $deliveryterms = $this->objectHelper->GetTradeDeliveryTermsType($code);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setApplicableTradeDeliveryTerms", $deliveryterms);
        return $this;
    }

    /**
     * Details of the associated confirmation of the order
     *
     * @param string $issuerassignedid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentSellerOrderReferencedDocument(string $issuerassignedid, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $sellerorderrefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, null, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setSellerOrderReferencedDocument", $sellerorderrefdoc);
        return $this;
    }

    /**
     * Details of the related order
     *
     * @param string $issuerassignedid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentBuyerOrderReferencedDocument(string $issuerassignedid, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $buyerorderrefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, null, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setBuyerOrderReferencedDocument", $buyerorderrefdoc);
        return $this;
    }

    /**
     * Details of the associated contract
     *
     * @param string $issuerassignedid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentContractReferencedDocument(string $issuerassignedid, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $contractrefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, null, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setContractReferencedDocument", $contractrefdoc);
        return $this;
    }

    /**
     * Details of the associated contract
     *
     * @param string $issuerassignedid
     * @param string|null $uriid
     * @param string|null $lineid
     * @param string|null $typecode
     * @param string|array|null $name
     * @param string|null $reftypecode
     * @param \DateTime|null $issueddate
     * @param string|null $binarydatafilename
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentAdditionalReferencedDocument(string $issuerassignedid, ?string $uriid = null, ?string $lineid = null, ?string $typecode = null, $name = null, ?string $reftypecode = null, ?\DateTime $issueddate = null, ?string $binarydatafilename = null): ZugferdDocumentBuilder
    {
        $additionalrefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, $uriid, $lineid, $typecode, $name, $reftypecode, $issueddate, $binarydatafilename);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "addToAdditionalReferencedDocument", $additionalrefdoc);
        return $this;
    }

    /**
     * Details of the related order
     *
     * @param string $issuerassignedid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentInvoiceReferencedDocument(string $issuerassignedid, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $invoicerefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, null, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setInvoiceReferencedDocument", $invoicerefdoc);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @param string $name
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentProcuringProject(string $id, string $name): ZugferdDocumentBuilder
    {
        $procuringproject = $this->objectHelper->GetProcuringProjectType($id, $name);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "setSpecifiedProcuringProject", $procuringproject);
        return $this;
    }

    /**
     * Details of the associated contract
     *
     * @param string $issuerassignedid
     * @param string|null $uriid
     * @param string|null $lineid
     * @param string|null $typecode
     * @param string|array|null $name
     * @param string|null $reftypecode
     * @param \DateTime|null $issueddate
     * @param string|null $binarydatafilename
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentUltimateCustomerOrderReferencedDocument($issuerassignedid, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $additionalrefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, null, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeAgreement, "addToAdditionalReferencedDocument", $additionalrefdoc);
        return $this;
    }

    /**
     * Detailed information on the actual delivery
     *
     * @param \DateTime|null $date
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentSupplyChainEvent(?\DateTime $date): ZugferdDocumentBuilder
    {
        $supplyChainevent = $this->objectHelper->GetSupplyChainEventType($date);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setActualDeliverySupplyChainEvent", $supplyChainevent);
        return $this;
    }

    /**
     * Detailed information on the associated shipping notification
     *
     * @param string $issuerassignedid
     * @param string|null $lineid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentDespatchAdviceReferencedDocument(string $issuerassignedid, ?string $lineid = null, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $despatchddvicerefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, $lineid, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setDespatchAdviceReferencedDocument", $despatchddvicerefdoc);
        return $this;
    }

    /**
     * Detailed information on the associated shipping notification
     *
     * @param string $issuerassignedid
     * @param string|null $lineid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentReceivingAdviceReferencedDocument(string $issuerassignedid, ?string $lineid = null, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $receivingadvicerefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, $lineid, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setReceivingAdviceReferencedDocument", $receivingadvicerefdoc);
        return $this;
    }

    /**
     * Detailed information on the associated delivery note
     *
     * @param string $issuerassignedid
     * @param string|null $lineid
     * @param \DateTime|null $issueddate
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentDeliveryNoteReferencedDocument(string $issuerassignedid, ?string $lineid = null, ?\DateTime $issueddate = null): ZugferdDocumentBuilder
    {
        $deliverynoterefdoc = $this->objectHelper->GetReferencedDocumentType($issuerassignedid, null, $lineid, null, null, null, $issueddate, null);
        $this->objectHelper->TryCall($this->headerTradeDelivery, "setDeliveryNoteReferencedDocument", $deliverynoterefdoc);
        return $this;
    }

    /**
     * Add new payment means
     *
     * @param string $code
     * @param string|null $information
     * @param string|null $cardType
     * @param string|null $cardId
     * @param string|null $cardHolder
     * @param string|null $sellerIban
     * @param string|null $payeeIban
     * @param string|null $payeeAccountName
     * @param string|null $payeePropId
     * @param string|null $payeeFinInstitute
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentPaymentMeans(string $code, ?string $information = null, ?string $cardType = null, ?string $cardId = null, ?string $cardHolderName = null, ?string $buyerIban = null, ?string $payeeIban = null, ?string $payeeAccountName = null, ?string $payeePropId = null, ?string $payeeBic = null): ZugferdDocumentBuilder
    {
        $paymentMeans = $this->objectHelper->GetTradeSettlementPaymentMeansType($code, $information);
        $financialCard = $this->objectHelper->GetTradeSettlementFinancialCardType($cardType, $cardId, $cardHolderName);
        $buyerfinancialaccount = $this->objectHelper->GetDebtorFinancialAccountType($buyerIban);
        $payeefinancialaccount = $this->objectHelper->GetCreditorFinancialAccountType($payeeIban, $payeeAccountName, $payeePropId);
        $payeefinancialInstitution = $this->objectHelper->GetCreditorFinancialInstitutionType($payeeBic);

        $this->objectHelper->TryCall($paymentMeans, "setApplicableTradeSettlementFinancialCard", $financialCard);
        $this->objectHelper->TryCall($paymentMeans, "setPayerPartyDebtorFinancialAccount", $buyerfinancialaccount);
        $this->objectHelper->TryCall($paymentMeans, "setPayeePartyCreditorFinancialAccount", $payeefinancialaccount);
        $this->objectHelper->TryCall($paymentMeans, "setPayeeSpecifiedCreditorFinancialInstitution", $payeefinancialInstitution);

        $this->objectHelper->TryCall($this->headerTradeSettlement, "addToSpecifiedTradeSettlementPaymentMeans", $paymentMeans);

        return $this;
    }

    /**
     * Add Tax
     *
     * @param string $categoryCode
     * @param string $typeCode
     * @param float $basisAmount
     * @param float $calculatedAmount
     * @param float|null $rateApplicablePercent
     * @param string|null $exemptionReason
     * @param string|null $exemptionReasonCode
     * @param float|null $lineTotalBasisAmount
     * @param float|null $allowanceChargeBasisAmount
     * @param \DateTime|null $taxPointDate
     * @param string|null $dueDateTypeCode
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentTax(string $categoryCode, string $typeCode, float $basisAmount = 0, float $calculatedAmount = 0, ?float $rateApplicablePercent = null, ?string $exemptionReason = null, ?string $exemptionReasonCode = null, ?float $lineTotalBasisAmount = null, ?float $allowanceChargeBasisAmount = null, ?\DateTime $taxPointDate = null, ?string $dueDateTypeCode = null): ZugferdDocumentBuilder
    {
        $tax = $this->objectHelper->GetTradeTaxType($categoryCode, $typeCode, $basisAmount, $calculatedAmount, $rateApplicablePercent, $exemptionReason, $exemptionReasonCode, $lineTotalBasisAmount, $allowanceChargeBasisAmount, $taxPointDate, $dueDateTypeCode);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "addToApplicableTradeTax", $tax);
        return $this;
    }

    /**
     * Add Tax in a simple way
     *
     * @param string $categoryCode
     * @param string $typeCode
     * @param float $basisAmount
     * @param float $calculatedAmount
     * @param float|null $rateApplicablePercent
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentTaxSimple(string $categoryCode, string $typeCode, float $basisAmount = 0, float $calculatedAmount = 0, ?float $rateApplicablePercent = null): ZugferdDocumentBuilder
    {
        return $this->AddDocumentTax($categoryCode, $typeCode, $basisAmount, $calculatedAmount, $rateApplicablePercent);
    }

    /**
     * Sets the billing period
     *
     * @param \DateTime|null $startdate
     * @param \DateTime|null $endDate
     * @param string|null $description
     * @return ZugferdDocumentBuilder
     */
    public function SetDocumentBillingPeriod(?\DateTime $startdate, ?\DateTime $endDate, ?string $description): ZugferdDocumentBuilder
    {
        $period = $this->objectHelper->GetSpecifiedPeriodType($startdate, $endDate, null, $description);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "setBillingSpecifiedPeriod", $period);
        return $this;

    }

    /**
     * Add a allowance/charge at document level
     *
     * @param float $actualAmount
     * @param boolean $isCharge
     * @param string $taxTypeCode
     * @param string $taxCategoryCode
     * @param float $rateApplicablePercent
     * @param float|null $sequence
     * @param float|null $calculationPercent
     * @param float|null $basisAmount
     * @param float|null $basisQuantity
     * @param string|null $basisQuantityUnitCode
     * @param string|null $reasonCode
     * @param string|null $reason
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentTradeAllowanceCharge(float $actualAmount, bool $isCharge, string $taxTypeCode, string $taxCategoryCode, float $rateApplicablePercent, ?float $sequence = null, ?float $calculationPercent = null, ?float $basisAmount = null, ?float $basisQuantity = null, ?string $basisQuantityUnitCode = null, ?string $reasonCode = null, ?string $reason = null): ZugferdDocumentBuilder
    {
        $allowanceCharge = $this->objectHelper->GetTradeAllowanceChargeType($actualAmount, $isCharge, $taxTypeCode, $taxCategoryCode, $rateApplicablePercent, $sequence, $calculationPercent, $basisAmount, $basisQuantity, $basisQuantityUnitCode, $reasonCode, $reason);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "addToSpecifiedTradeAllowanceCharge", $allowanceCharge);
        return $this;
    }

    /**
     * Add a logistic service charge
     *
     * @param string $description
     * @param float $appliedAmount
     * @param array|null $taxTypeCodes
     * @param array|null $taxCategpryCodes
     * @param array|null $rateApplicablePercents
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentLogisticsServiceCharge(string $description, float $appliedAmount, ?array $taxTypeCodes = null, ?array $taxCategpryCodes = null, ?array $rateApplicablePercents = null): ZugferdDocumentBuilder
    {
        $logcharge = $this->objectHelper->GetLogisticsServiceChargeType($description, $appliedAmount, $taxTypeCodes, $taxCategpryCodes, $rateApplicablePercents);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "addToSpecifiedLogisticsServiceCharge", $logcharge);
        return $this;
    }

    /**
     * Add a payment term
     *
     * @param string|null $description
     * @param \DateTime|null $dueDate
     * @param string|null $directDebitMandateID
     * @return ZugferdDocumentBuilder
     */
    public function AddDocumentPaymentTerms(?string $description = null, ?\DateTime $dueDate = null, ?string $directDebitMandateID = null): ZugferdDocumentBuilder
    {
        $paymentTerms = $this->objectHelper->GetTradePaymentTermsType($description, $dueDate, $directDebitMandateID);
        $this->objectHelper->TryCall($this->headerTradeSettlement, "addToSpecifiedTradePaymentTerms", $paymentTerms);
        return $this;
    }
}
