<?php

namespace App\Service;

use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\PaymentChannel;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Request\RetrievePaymentRequest;
use Iyzipay\Model\Payment;
use Iyzipay\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class IyzicopayService
{
    private Options $options;

    public function __construct(ParameterBagInterface $params)
    {
        $this->options = new Options();
        $this->options->setApiKey($params->get('IYZICO_API_KEY'));
        $this->options->setSecretKey($params->get('IYZICO_SECRET_KEY'));
        $this->options->setBaseUrl($params->get('IYZICO_BASE_URL'));
    }

    public function createPaymentRequest(array $paymentData)
    {
        // Ödeme kartı bilgisi
        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($paymentData['cardHolderName']);
        $paymentCard->setCardNumber($paymentData['cardNumber']);
        $paymentCard->setExpireMonth($paymentData['expireMonth']);
        $paymentCard->setExpireYear($paymentData['expireYear']);
        $paymentCard->setCvc($paymentData['cvc']);

        // Alıcı (Buyer) bilgisi
        $buyer = new Buyer();
        $buyer->setId($paymentData['buyerId']);
        $buyer->setName($paymentData['buyerName']);
        $buyer->setSurname($paymentData['buyerSurname']);
        $buyer->setGsmNumber($paymentData['gsmNumber']);
        $buyer->setEmail($paymentData['email']);
        $buyer->setIdentityNumber($paymentData['identityNumber']);
        $buyer->setLastLoginDate($paymentData['lastLoginDate']);
        $buyer->setRegistrationDate($paymentData['registrationDate']);
        $buyer->setRegistrationAddress($paymentData['registrationAddress']);
        $buyer->setIp($paymentData['ip']);
        $buyer->setCity($paymentData['city']);
        $buyer->setCountry($paymentData['country']);
        $buyer->setZipCode($paymentData['zipCode']);

        // Fatura adresi
        $billingAddress = new Address();
        $billingAddress->setContactName($paymentData['contactName']);
        $billingAddress->setCity($paymentData['city']);
        $billingAddress->setCountry($paymentData['country']);
        $billingAddress->setAddress($paymentData['address']);
        $billingAddress->setZipCode($paymentData['zipCode']);

        // Sepet öğeleri
        $basketItems = [];
        foreach ($paymentData['basketItems'] as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId((string) $item['id']);
            $basketItem->setName($item['name']);
            $basketItem->setCategory1($item['category'] ?? 'Ürün');
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice((string) $item['price']);
            $basketItems[] = $basketItem;
        }

        // Ödeme isteği
        $request = new CreatePaymentRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($paymentData['conversationId']);
        $request->setPrice($paymentData['price']);
        $request->setPaidPrice($paymentData['paidPrice']);
        $request->setCurrency(Currency::TL);
        $request->setInstallment($paymentData['installment'] ?? 1);
        $request->setBasketId($paymentData['basketId']);
        $request->setPaymentChannel(PaymentChannel::WEB);
        $request->setPaymentGroup(PaymentGroup::PRODUCT);
        $request->setPaymentCard($paymentCard);
        $request->setBuyer($buyer);
        $request->setBillingAddress($billingAddress);
        $request->setShippingAddress($billingAddress);
        $request->setBasketItems($basketItems);

        return $request;
    }

    public function processPayment(CreatePaymentRequest $request)
    {
        return Payment::create($request, $this->options);
    }

    public function retrievePayment(string $paymentId)
    {
        $request = new RetrievePaymentRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId(uniqid());
        $request->setPaymentId($paymentId);

        return Payment::retrieve($request, $this->options);
    }
}
