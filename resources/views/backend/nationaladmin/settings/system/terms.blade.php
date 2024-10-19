
                    <div class="form-group row">
                        <div class="col-12">
                          <textarea class="form-control @error('tos') is_invalid @enderror" name="tos" id="tos" value="{{( $SystemSetting->tos)}}">{{( $SystemSetting->tos)}}</textarea>
                        </div>
                        @error('tos')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>
