import $ from "jquery";
import axios from "axios";
import {Controller} from '@hotwired/stimulus';

import common from '../common';

/*
 * create user controller
 */
export default class CreateUser extends Controller {
  connect() {
    $(".dropdown-content").css('display', 'none');
    $(document).mousedown('click.document.createUser', function (event) {
      if ($(event.target).closest(".pmodal").length === 0) {
        $('.pmodal-background').remove();
      }
    });
    $(this.element).find('input[name=login]').focus();
  }

  close(event) {
    event?.stopPropagation();
    $('.pmodal-background').remove();
  }

  submit(event) {
    event.stopPropagation();
    $(this.element).find('.create-btn').remove();
    $(this.element).find('.cancel-btn').remove();
    $(this.element).find('.create-btn').css('display', 'none');
    $(this.element).find('.wait-btn').css('display', 'flex');

    let login = $(this.element).find('input[name=login]').val();
    let role = $(this.element).find('select[name=role]').val();
    let email = $(this.element).find('input[name=email]').val()??'';
    let ad = $(this.element).find('select[name=ad]').val();
    let sam = $(this.element).find('input[name=sam]').val()??'';
    let dn = $(this.element).find('input[name=dn]').val()??'';
    let firstName = $(this.element).find('input[name=firstName]').val()??'';
    let lastName = $(this.element).find('input[name=lastName]').val()??'';
    if($(this.element).find('select[name=ad]').val() === undefined){
      ad = null;
    }

    axios.post('/api/user/', {
      login,
      role,
      email: email,
      adId: ad,
      adSamAccount: sam,
      adDn: dn,
      lastName: lastName,
      firstName: firstName
    }).then( response => {
      let $clone = $($("#lineTemplate").html());
      $clone.attr("id", response.data.id);
      $clone.find(".row-id" ).text(response.data.id);
      $clone.find('.row-login').text(response.data.login);
      $clone.find('.row-firstName').text(response?.data.firstName);
      $clone.find('.row-lastName').text(response?.data.lastName);
      $clone.find('.row-state').text(response.data.state?.state);
      $clone.find('.row-roles').text(response?.data?.roles[0].toString());
      $clone.find('.ad').text(response?.data?.ad?.ad);
      $clone.find('.row-samAccount').text(response?.data['sam_account']);
      $clone.find('.row-email').text(response.data.email);
      $clone.find('*[data-user-id]').attr('data-user-id', response.data.id);
      $clone.find('*[data-name]').attr('data-name', response.data.login);
      if(response?.data['ad_dn'] !== null){
        $clone.find('.row-adDn').text(response?.data['ad_dn'].substring(0,17));
      }
      $('tbody').prepend($clone);
      common.toast({
        type: common.getEnum().NOTIF_SUCCESS,
        message: 'Utilisateur créé'
      });
    }).catch(err => {
      switch (err.response.data.errorMessage) {
        case 'MAIL_WAS_NOT_SEND':
          const user = common.getControllerByIdentifier(this, "user");
          user.getContent();
          common.toast({
            type: common.getEnum().NOTIF_ERROR,
            message: 'L\'email n\'a pas pu être envoyé.'
          });
          break;
        case 'USER_LOGIN_IS_ALREADY_USED':
          common.toast({
            type: common.getEnum().NOTIF_ERROR,
            message: 'L\'utilisateur existe déjà.'
          });
          break;
        case 'USER_MAIL_IS_ALREADY_USED':
          common.toast({
            type: common.getEnum().NOTIF_ERROR,
            message: 'L\'adresse email est déjà utilisé.'
          });
          break;
        default:
          common.toast({
            type: common.getEnum().NOTIF_ERROR,
            message: 'Echec lors de création de l\'utilisateur.'
          });
      }
    }).finally(() => {
      this.close();
    });
  }
}